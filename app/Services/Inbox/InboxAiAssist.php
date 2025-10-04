<?php

namespace App\Services\Inbox;

use App\Models\Fragment;
use OpenAI\Laravel\Facades\OpenAI;

class InboxAiAssist
{
    protected string $model;

    protected float $temperature;

    protected bool $titlesEnabled;

    protected bool $summariesEnabled;

    protected bool $suggestEditsEnabled;

    public function __construct()
    {
        $this->model = config('inbox.ai.model', 'gpt-4o-mini');
        $this->temperature = config('inbox.ai.temperature', 0.2);
        $this->titlesEnabled = config('inbox.ai.titles_enabled', false);
        $this->summariesEnabled = config('inbox.ai.summaries_enabled', false);
        $this->suggestEditsEnabled = config('inbox.ai.suggest_edits_enabled', false);
    }

    /**
     * Generate AI-enhanced title for fragment
     */
    public function generateTitle(Fragment $fragment): ?string
    {
        if (! $this->titlesEnabled || ! $fragment->message) {
            return null;
        }

        try {
            $prompt = $this->buildTitlePrompt($fragment);

            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that creates concise, descriptive titles for content fragments.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 100,
                'temperature' => $this->temperature,
            ]);

            return trim($response->choices[0]->message->content ?? '');
        } catch (\Exception $e) {
            \Log::warning('AI title generation failed', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate AI summary for fragment
     */
    public function generateSummary(Fragment $fragment): ?string
    {
        if (! $this->summariesEnabled || ! $fragment->message) {
            return null;
        }

        try {
            $prompt = $this->buildSummaryPrompt($fragment);

            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that creates concise summaries of content fragments.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 200,
                'temperature' => $this->temperature,
            ]);

            return trim($response->choices[0]->message->content ?? '');
        } catch (\Exception $e) {
            \Log::warning('AI summary generation failed', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Suggest edits for fragment categorization and tagging
     */
    public function suggestEdits(Fragment $fragment): ?array
    {
        if (! $this->suggestEditsEnabled || ! $fragment->message) {
            return null;
        }

        try {
            $prompt = $this->buildEditSuggestionsPrompt($fragment);

            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that suggests appropriate categorization, tags, and metadata for content fragments. Return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 300,
                'temperature' => $this->temperature,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            return json_decode($content, true);
        } catch (\Exception $e) {
            \Log::warning('AI edit suggestions failed', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get AI assist data for fragment (title, summary, suggestions)
     */
    public function getAiAssistData(Fragment $fragment): array
    {
        $data = [];

        if ($this->titlesEnabled) {
            $data['suggested_title'] = $this->generateTitle($fragment);
        }

        if ($this->summariesEnabled) {
            $data['summary'] = $this->generateSummary($fragment);
        }

        if ($this->suggestEditsEnabled) {
            $data['suggested_edits'] = $this->suggestEdits($fragment);
        }

        return $data;
    }

    /**
     * Build prompt for title generation
     */
    protected function buildTitlePrompt(Fragment $fragment): string
    {
        $content = substr($fragment->message, 0, 500); // Limit content length

        return "Create a concise, descriptive title (max 50 characters) for this content:\n\n{$content}\n\nType: {$fragment->type}\nTags: ".implode(', ', $fragment->tags ?? []);
    }

    /**
     * Build prompt for summary generation
     */
    protected function buildSummaryPrompt(Fragment $fragment): string
    {
        $content = substr($fragment->message, 0, 1000); // Limit content length

        return "Create a concise summary (2-3 sentences max) for this content:\n\n{$content}\n\nType: {$fragment->type}";
    }

    /**
     * Build prompt for edit suggestions
     */
    protected function buildEditSuggestionsPrompt(Fragment $fragment): string
    {
        $content = substr($fragment->message, 0, 800); // Limit content length

        $availableTypes = ['todo', 'note', 'link', 'document', 'event', 'contact', 'log', 'ai_response'];

        return "Analyze this content and suggest appropriate categorization. Return JSON with 'type', 'tags' (array), and 'category' fields:\n\n{$content}\n\nCurrent type: {$fragment->type}\nCurrent tags: ".json_encode($fragment->tags ?? [])."\n\nAvailable types: ".implode(', ', $availableTypes)."\n\nRespond with valid JSON only.";
    }
}
