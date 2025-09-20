<?php

/**
 * Short Description Goes Here
 *
 * @author Chrispian H. Burks <chrispian.burks@webvdevstudios.com>
 *
 * @since 4/24/25
 */

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Support\Facades\Log;

class SuggestTags
{
    public function handle(Fragment $fragment, $next)
    {
        $fragment = $this->__invoke($fragment);

        return $next($fragment);
    }

    public function __invoke(Fragment $fragment): Fragment
    {

        Log::debug('SuggestTags::invoke()');
        $keywords = [
            'todo' => 'task',
            'insight' => 'idea',
            'link' => 'url',
            'reminder' => 'time',
        ];

        $suggested = [];

        foreach ($keywords as $term => $tag) {
            if (str_contains(strtolower($fragment->message), $term)) {
                $suggested[] = $tag;
            }
        }

        $fragment->tags = array_unique(array_merge($fragment->tags ?? [], $suggested));
        $fragment->save();

        return $fragment;
    }
}
