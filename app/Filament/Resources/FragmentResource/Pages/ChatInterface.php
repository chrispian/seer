<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use App\Models\Bookmark;
use App\Models\Fragment;
use Filament\Resources\Pages\Page;
use Illuminate\Pipeline\Pipeline;
use OpenAI\Laravel\Facades\OpenAI;


class ChatInterface extends Page
{

    protected static string $resource = FragmentResource::class;
    protected static ?string $slug = 'lens';

    public $input = '';
    public $chatHistory = [];

    protected static string $view = 'filament.resources.fragment-resource.pages.chat-interface';
    public static function shouldRegisterNavigation( array $parameters = [] ): bool
    {
        return false;
    }

    public function getLayout(): string
    {
        return 'vendor.filament-panels.components.layout.base'; // basic Filament layout
    }

    protected static ?string $title = null;
    protected ?string $heading = null;
    protected static ?string $breadcrumb = null;

    public function getTitle(): string
    {
        return '';
    }

    public function getBreadcrumb(): string
    {
        return '';
    }


    public function mount()
    {
        $this->chatHistory = Fragment::latest()->take(20)->get()->reverse()->values()->toArray();
    }

    public function handleInput()
    {
        $message = trim($this->input);
        $this->input = '';

        $fragment = Fragment::create([
            'vault' => 'default',
            'type' => 'log',
            'message' => $message,
            'source' => 'chat',
        ]);

        $this->chatHistory[] = $fragment->toArray();

        if (str_starts_with($message, ':frag')) {
            $parsed = $this->parseFragment($message);

            $fragment = Fragment::create([
                'type' => $parsed['type'] ?? 'log',
                'message' => $parsed['message'] ?? $message,
                'vault' => $parsed['vault'] ?? 'default',
                'tags' => $parsed['tags'] ?? [],
                'metadata' => $parsed['metadata'] ?? [],
                'state' => $parsed['state'] ?? [],
                'source' => 'gpt',
            ]);

            $processed = app(Pipeline::class)
                ->send($fragment)
                ->through([
                    // \App\Actions\ParseAtomicFragment::class,
                    \App\Actions\ParseChaosFragment::class,
                    // \App\Actions\EnrichFragmentWithLlama::class,
                    // \App\Actions\InferFragmentType::class,
                    // \App\Actions\SuggestTags::class,
                    // \App\Actions\RouteToVault::class,
                ])
                ->thenReturn();

            $this->chatHistory[] = [
                'type' => 'summary',
                'message' => $processed['summary'],
                'metadata' => $processed,
            ];
        }

        if (str_starts_with($message, ':clear')) {
            $this->chatHistory = [];
            return;
        }

        if (str_starts_with($message, ':help')) {
            $this->chatHistory[] = [
                'type' => 'system',
                'message' => $this->renderHelpMarkdown(),
            ];
            return;
        }


        // Handle Recall
        if (str_starts_with($message, ':recall')) {
            $summary = $this->runRecallByType($message);
            $this->chatHistory[] = [
                'type' => 'recall',
                'message' => $summary,
            ];
            return;
        }

        // Bookmark

        if (str_starts_with($message, ':bookmark')) {
            $summary = $this->handleBookmark($message);
            $this->chatHistory[] = [
                'type' => 'bookmark',
                'message' => $summary,
            ];
            return;
        }



    }


    protected function gpt(string $prompt): string
    {

//        $response = OpenAI::models()->list();
//
//        dd(collect($response->data)->pluck('id'));
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    protected function parseFragment(string $input): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<PROMPT
You are an API parser. ONLY return a valid JSON object. Do not include explanation, notes, or formatting like ```json.

The format is:

{
  "type": "log",
  "message": "...",
  "tags": ["tag1", "tag2"],
  "metadata": { "confidence": 0.9 },
  "state": { "status": "open" },
  "vault": "default"
}

Always return JSON, even if you must guess. Never include markdown or explanations.
PROMPT
                ],
                [
                    'role' => 'user',
                    'content' => $input,
                ],
            ],
        ]);

        $content = trim($response->choices[0]->message->content ?? '');

        $parsed = json_decode($content, true);

        if (!is_array($parsed)) {
            logger()->warning('GPT returned invalid JSON', ['raw' => $content]);

            return [
                'type' => 'log',
                'message' => $input,
                'tags' => [],
                'metadata' => ['error' => 'gpt_parse_failed', 'raw' => $content],
                'state' => [],
                'vault' => 'default',
            ];
        }

        return $parsed;
    }


    protected function runRecallCommand(string $input): string
    {
        $args = str_replace(':recall', '', $input);
        $args = trim($args);

        $query = Fragment::query();

        if (preg_match('/type:(\w+)/', $args, $match)) {
            $query->where('type', $match[1]);
        }

        if (preg_match('/tag:(\w+)/', $args, $match)) {
            $query->whereJsonContains('tags', $match[1]);
        }

        if (preg_match('/last\s+(\d+)/', $args, $match)) {
            $query->latest()->limit((int) $match[1]);
        } else {
            $query->latest()->limit(5); // Default to 5
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return 'No fragments found.';
        }

        return $results->map(fn ($f) => "- [{$f->type}] {$f->message}")->implode("\n");
    }


    protected function runRecallByType(string $input): string
    {
        // Clean up the input
        $input = trim(str_replace(':recall', '', $input));

        preg_match('/type:(\w+)/', $input, $typeMatch);
        preg_match('/limit:(\d+)/', $input, $limitMatch);

        $type = $typeMatch[1] ?? null;
        $limit = (int) ($limitMatch[1] ?? 5);

        if (!$type) {
            return 'Please specify a fragment type (e.g. `:recall type:todo`).';
        }

        $results = \App\Models\Fragment::where('type', $type)
            ->latest()
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            return "No fragments found of type `{$type}`.";
        }

        $fragmentPrefix = '';
        if ($type === 'todo') {
            $fragmentPrefix = '- [ ] ';
        }

        return $results
                ->map(fn ($f) => "\n{$fragmentPrefix}{$f->message}")
                ->implode("\n");


    }


    protected function handleBookmark(string $input): string
    {
        $cmd = trim(str_replace(':bookmark', '', $input));

        // 1. LIST
        if ($cmd === 'list') {
            $bookmarks = \App\Models\Bookmark::latest()->limit(10)->get();
            if ($bookmarks->isEmpty()) return 'No bookmarks found.';
            return "### Bookmarks\n\n" . $bookmarks->map(fn ($b) => "- `{$b->name}` (" . count($b->fragment_ids) . " items)")->implode("\n");
        }

        // 2. SHOW
        if (str_starts_with($cmd, 'show')) {
            $key = trim(str_replace('show', '', $cmd));
            $bookmark = \App\Models\Bookmark::where('name', 'like', "%{$key}%")->first();
            if (!$bookmark) return "No bookmark matching `{$key}` found.";

            $fragments = Fragment::whereIn('id', $bookmark->fragment_ids)->get();
            foreach ($fragments as $frag) {
                $this->chatHistory[] = [
                    'type' => 'bookmark',
                    'message' => $frag->message,
                ];
            }

            return "Bookmark `{$bookmark->name}` restored to chat.";
        }


        if (str_starts_with($cmd, 'forget')) {
            $key = trim(str_replace('forget', '', $cmd));
            $bookmark = \App\Models\Bookmark::where('name', 'like', "%{$key}%")->first();

            if (!$bookmark) {
                return "No bookmark found for `{$key}`.";
            }

            $bookmark->delete();

            return "❌ Bookmark `{$bookmark->name}` forgotten.";
        }


        // 3. CREATE BOOKMARK

        // Get the last non-system message
        $last = Fragment::latest()->first();
        if (!$last) return 'No fragments found to bookmark.';

        $fragmentIds = [];

        if ($last->type === 'chaos' && isset($last->metadata['children'])) {
            $fragmentIds = $last->metadata['children'];
        } else {
            $fragmentIds = [$last->id];
        }

        $title = \Illuminate\Support\Str::slug(substr($last->message, 0, 30)) . '-' . now()->format('His');

        Bookmark::create([
            'name' => $title,
            'fragment_ids' => $fragmentIds,
        ]);

        return "📌 Bookmarked as `{$title}` (" . count($fragmentIds) . " fragment" . (count($fragmentIds) > 1 ? 's' : '') . ").";
    }


    protected function renderHelpMarkdown(): string
    {
        return <<<MARKDOWN
### Commands Cheat Sheet

Here are the commands you can use in the chat:

#### 🔍 Recall & Memory
- `:recall type:todo limit:5` – Recall recent fragments by type
- `:bookmark` – Bookmark the most recent fragment (or all from chaos)
- `:bookmark list` – List saved bookmarks
- `:bookmark show {hint}` – Replay a bookmark in the chat
- `:bookmark forget {hint}` – Delete a saved bookmark

#### 🔁 Fragments
- `:frag This is something worth remembering` – Log a fragment
- `:chaos This contains multiple items. Do X. Also do Y.` – Split and log a chaos fragment

#### 🧹 Chat Tools
- `:clear` – Clear the current chat view
- `:help` – Show this help message

---
All fragments are stored and processed. Bookmarks give you quick access to curated sets.
MARKDOWN;

    }



}
