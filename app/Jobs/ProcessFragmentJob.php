<?php

namespace App\Jobs;

use App\Events\FragmentProcessed;
use App\Models\Fragment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFragmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Fragment $fragment;

    public function __construct(Fragment $fragment)
    {
        $this->fragment = $fragment;
    }

    public function handle()
    {
        $messages = [];
        $fragments = [];

        DB::beginTransaction();

        try {
            Log::info('🔧 Processing Fragment', ['fragment_id' => $this->fragment->id]);

            $processed = app(Pipeline::class)
                ->send($this->fragment)
                ->through([
                    \App\Actions\ParseChaosFragment::class,
                    \App\Actions\DriftSync::class,
                    \App\Actions\ParseAtomicFragment::class,
                    \App\Actions\ExtractMetadataEntities::class,
                    \App\Actions\GenerateAutoTitle::class,
                    \App\Actions\EnrichFragmentWithLlama::class,
                    \App\Actions\InferFragmentType::class,
                    \App\Actions\SuggestTags::class,
                    \App\Actions\RouteToVault::class,
                ])
                ->thenReturn();

            $messages[] = "📦 Fragment stored: `{$this->fragment->message}`";

            $fragments[] = [
                'id' => $this->fragment->id,
                'type' => $this->fragment->type,
                'message' => $this->fragment->message,
            ];


            DB::commit();

            Log::info('✅ Fragment processing complete', ['fragment_id' => $this->fragment->id]);

        } catch (\Throwable $e) {
            DB::rollBack();

            $messages[] = "⚠️ Failed to store fragment: {$e->getMessage()}";

            Log::error('❌ Fragment processing failed', [
                'fragment_id' => $this->fragment->id,
                'error' => $e->getMessage(),
            ]);
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
