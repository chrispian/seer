<?php

namespace App\Actions;

use App\Models\Fragment;

class InferFragmentType
{
    public function __invoke(Fragment $fragment): Fragment
    {
        if ($fragment->type !== 'note') {
            return $fragment; // already has a type from ParseAtomicFragment
        }

        // Simple rules or send to GPT/LLaMA if needed
        if (str_starts_with(strtolower($fragment->message), 'http')) {
            $fragment->type = 'bookmark';
        } elseif (str_contains($fragment->message, 'buy') || str_contains($fragment->message, 'call')) {
            $fragment->type = 'todo';
        } else {
            $fragment->type = 'log';
        }

        $fragment->save();

        return $fragment;
    }

}
