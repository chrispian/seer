<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\Vault;
use App\Services\VaultRoutingRuleService;
use Illuminate\Support\Facades\Log;

class RouteToVault
{
    public function __construct(protected VaultRoutingRuleService $routingService) {}

    public function __invoke(Fragment $fragment): Fragment
    {
        Log::debug('RouteFragment::invoke()');
        $message = $fragment->message;

        // Extract `vault:xyz` from anywhere in the message
        if (preg_match('/vault:\s*([a-zA-Z0-9_\-]+)/i', $message, $matches)) {
            $fragment->vault = $matches[1];

            // Clean vault directive from the message
            $message = preg_replace('/vault:\s*[a-zA-Z0-9_\-]+\s*/i', '', $message);
            $fragment->message = trim($message);
        }

        // If vault not set by directive, try rule-driven routing
        if (empty($fragment->vault)) {
            $routingTarget = $this->routingService->resolveForFragment($fragment);

            if ($routingTarget) {
                if (isset($routingTarget['vault'])) {
                    $fragment->vault = $routingTarget['vault'];
                }
                if (isset($routingTarget['project_id'])) {
                    $fragment->project_id = $routingTarget['project_id'];
                }
            }
        }

        // Final fallback to default vault if still no vault set
        if (empty($fragment->vault)) {
            $defaultVault = Vault::getDefault();
            if ($defaultVault) {
                $fragment->vault = $defaultVault->name;
                if (! $fragment->project_id) {
                    $defaultProject = \App\Models\Project::getDefaultForVault($defaultVault->id);
                    if ($defaultProject) {
                        $fragment->project_id = $defaultProject->id;
                    }
                }
            } else {
                $fragment->vault = 'default';
            }
        }

        $fragment->save();

        return $fragment;
    }
}
