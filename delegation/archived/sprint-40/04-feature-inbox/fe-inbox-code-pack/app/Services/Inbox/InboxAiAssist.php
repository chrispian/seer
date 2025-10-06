<?php

namespace App\Services\Inbox;

use App\Services\Inbox\Prompts\PromptFactory;
use Illuminate\Support\Facades\Config;

class InboxAiAssist
{
    public function maybeAssist(array $frag, array $updates, array $override = []): array
    {
        $cfg = [
            'titles' => $override['titles'] ?? Config::get('inbox.ai.titles', false),
            'summaries' => $override['summaries'] ?? Config::get('inbox.ai.summaries', false),
            'suggest_edit' => $override['suggest_edit'] ?? Config::get('inbox.ai.suggest_edit', false),
            'model' => Config::get('inbox.ai.model'),
            'temperature' => Config::get('inbox.ai.temperature'),
        ];

        $result = [];

        if ($cfg['titles'] && empty($updates['title'])) {
            $result['title'] = $this->generate('title', $frag, $updates, $cfg);
        }
        if ($cfg['summaries']) {
            $result['summary'] = $this->generate('summary', $frag, $updates, $cfg);
        }
        if ($cfg['suggest_edit']) {
            $suggested = $this->generate('suggest_edit', $frag, $updates, $cfg);
            if ($suggested) {
                // Store suggestion in metadata or edited_message; for MVP, edited_message.
                $result['edited_message'] = $suggested;
            }
        }

        return $result;
    }

    protected function generate(string $kind, array $frag, array $updates, array $cfg): ?string
    {
        $prompt = PromptFactory::make($kind, $frag, $updates);

        // TODO: call your LLM client here using $cfg['model'].
        // Return placeholder for now.
        return null; // Keep deterministic for MVP unless enabled with a client.
    }
}
