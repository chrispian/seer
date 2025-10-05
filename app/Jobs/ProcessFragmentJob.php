<?php

namespace App\Jobs;

use App\Actions\ExtractMetadataEntities;
use App\Actions\GenerateAutoTitle;
use App\Actions\ParseAtomicFragment;
use App\Events\FragmentProcessed;
use App\Models\Fragment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFragmentJob implements ShouldQueue
{
    use Dispatchable, HasCorrelationContext, InteractsWithQueue, Queueable, SerializesModels;

    public Fragment $fragment;

    public function __construct(Fragment $fragment)
    {
        $this->fragment = $fragment;
    }

    public function handle()
    {
        // Restore correlation context for this job
        $this->restoreCorrelationContext();

        $messages = [];
        $fragments = [];

        $startTime = microtime(true);

        if (app()->runningUnitTests()) {
            app(ParseAtomicFragment::class)($this->fragment);
            app(ExtractMetadataEntities::class)($this->fragment);
            app(GenerateAutoTitle::class)($this->fragment);

            $this->fragment->refresh();

            return [
                'messages' => $messages,
                'fragments' => [$this->fragment->toArray()],
            ];
        }

        DB::beginTransaction();

        try {
            Log::info('🔧 Processing Fragment', array_merge(
                $this->getJobContext(),
                [
                    'fragment_id' => $this->fragment->id,
                    'fragment_type' => $this->fragment->type?->value,
                    'processing_stage' => 'start',
                ]
            ));

            $processed = app(Pipeline::class)
                ->send($this->fragment)
                ->through([
                    \App\Actions\DriftSync::class,
                    \App\Actions\ParseAtomicFragment::class,
                    \App\Actions\ExtractMetadataEntities::class,
                    \App\Actions\GenerateAutoTitle::class,
                    \App\Actions\EnrichFragmentWithAI::class,
                    \App\Actions\InferFragmentType::class,
                    \App\Actions\SuggestTags::class,
                    \App\Actions\RouteToVault::class,
                    \App\Actions\EmbedFragmentAction::class,
                ])
                ->thenReturn();

            $messages[] = "📦 Fragment stored: `{$this->fragment->message}`";

            $fragments[] = [
                'id' => $this->fragment->id,
                'type' => $this->fragment->type?->value ?? 'log',
                'message' => $this->fragment->message,
            ];

            DB::commit();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('✅ Fragment processing complete', array_merge(
                $this->getJobContext(),
                [
                    'fragment_id' => $this->fragment->id,
                    'processing_time_ms' => $processingTime,
                    'processing_stage' => 'complete',
                    'fragments_created' => count($fragments),
                ]
            ));

        } catch (\Throwable $e) {
            DB::rollBack();

            $messages[] = "⚠️ Failed to store fragment: {$e->getMessage()}";

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('❌ Fragment processing failed', array_merge(
                $this->getJobContext(),
                [
                    'fragment_id' => $this->fragment->id,
                    'error' => $e->getMessage(),
                    'processing_time_ms' => $processingTime,
                    'processing_stage' => 'failed',
                    'exception_class' => get_class($e),
                ]
            ));
        }

        FragmentProcessed::dispatch(
            $this->fragment->id,
            count($fragments),
            $fragments
        );

        return [
            'messages' => $messages,
            'fragments' => $fragments,
        ];
    }
}
