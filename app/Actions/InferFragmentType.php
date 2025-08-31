<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\Type;
use App\Services\AI\TypeInferenceService;
use Illuminate\Support\Facades\Log;

class InferFragmentType
{
    protected TypeInferenceService $typeInference;

    public function __construct(TypeInferenceService $typeInference)
    {
        $this->typeInference = $typeInference;
    }

    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('InferFragmentType: Starting type inference', [
            'fragment_id' => $fragment->id,
            'current_type' => $fragment->type,
            'current_type_id' => $fragment->type_id,
        ]);

        // Skip if fragment already has a proper type relationship
        if ($fragment->type_id && $fragment->type) {
            Log::debug('InferFragmentType: Fragment already has type, skipping', [
                'fragment_id' => $fragment->id,
                'type' => $fragment->type,
            ]);
            return $fragment;
        }

        try {
            // Use AI-powered type inference
            $updatedFragment = $this->typeInference->applyTypeToFragment($fragment);
            
            Log::info('InferFragmentType: Type inference completed', [
                'fragment_id' => $updatedFragment->id,
                'assigned_type' => $updatedFragment->type,
                'type_id' => $updatedFragment->type_id,
            ]);

            return $updatedFragment;

        } catch (\Exception $e) {
            Log::error('InferFragmentType: AI inference failed, using fallback', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to basic rules if AI fails
            return $this->fallbackTypeInference($fragment);
        }
    }

    /**
     * Fallback type inference using simple rules
     */
    protected function fallbackTypeInference(Fragment $fragment): Fragment
    {
        $typeValue = 'log'; // Default

        // Simple rules for common patterns
        if (str_starts_with(strtolower($fragment->message), 'http')) {
            $typeValue = 'bookmark';
        } elseif (str_contains(strtolower($fragment->message), 'todo') || 
                  str_contains(strtolower($fragment->message), 'task')) {
            $typeValue = 'todo';
        } elseif (str_contains(strtolower($fragment->message), 'meeting')) {
            $typeValue = 'meeting';
        } elseif (str_contains(strtolower($fragment->message), 'note')) {
            $typeValue = 'note';
        }

        // Find or create the type
        $type = Type::where('value', $typeValue)->first();
        if (!$type) {
            // Fallback to log if type doesn't exist
            $type = Type::where('value', 'log')->first();
            $typeValue = 'log';
        }

        if ($type) {
            $fragment->update([
                'type' => $typeValue,
                'type_id' => $type->id,
            ]);

            Log::info('InferFragmentType: Applied fallback type', [
                'fragment_id' => $fragment->id,
                'type' => $typeValue,
                'type_id' => $type->id,
            ]);
        }

        return $fragment->fresh();
    }
}
