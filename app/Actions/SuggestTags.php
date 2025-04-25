<?php
/**
 * Short Description Goes Here
 *
 * @author Chrispian H. Burks <chrispian.burks@webvdevstudios.com>
 * @package App\Actions
 * @since 4/24/25
 */

namespace App\Actions;

use App\Models\Fragment;

class SuggestTags
{

    public function __invoke(Fragment $fragment): Fragment
    {
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
