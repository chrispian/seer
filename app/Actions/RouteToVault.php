<?php

namespace App\Actions;

use App\Models\Fragment;

class RouteToVault
{
    public function __invoke(Fragment $fragment): Fragment
    {
        $message = $fragment->message;

        // Extract `vault:xyz` from anywhere in the message
        if (preg_match('/vault:\s*([a-zA-Z0-9_\-]+)/i', $message, $matches)) {
            $fragment->vault = $matches[1];

            // Clean vault directive from the message
            $message = preg_replace('/vault:\s*[a-zA-Z0-9_\-]+\s*/i', '', $message);
            $fragment->message = trim($message);
        }

        // Default fallback
        if (empty($fragment->vault)) {
            $fragment->vault = 'default';
        }

        $fragment->save();

        return $fragment;
    }
}
