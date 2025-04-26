<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use App\Models\Bookmark;
use App\Models\Fragment;
use Filament\Resources\Pages\Page;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use OpenAI\Laravel\Facades\OpenAI;


class ChatInterface extends Page
{

    protected static string $resource = FragmentResource::class;
    protected static ?string $slug = 'lens';

    public $input = '';
    public $chatHistory = [];

    protected static string $view = 'filament.resources.fragment-resource.pages.chat-interface';
    public $recalledTodos = [];

    public ?array $currentSession = null;

    public ?Carbon $lastActivityAt = null;
    public int $sessionTimeoutMinutes = 60; // ← default to 1 hour inactivity


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
        $this->chatHistory = Fragment::latest()
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->toArray();

        $this->recalledTodos = []; // 👈 Add this!

    }

    public function handleInput()
    {
        $message = trim($this->input);
        $this->input = '';

        // Check session timeout
        if ($this->currentSession && $this->lastActivityAt) {
            if ($this->lastActivityAt->diffInMinutes(now()) >= $this->sessionTimeoutMinutes) {
                $this->endSession('Timeout');
            }
        }

        $this->lastActivityAt = now();

        $fragmentData = [
            'vault' => 'default',
            'type' => 'log',
            'message' => $message,
            'source' => 'chat',
            'tags' => [],
            'state' => [],
        ];

        if ($this->currentSession) {
            $fragmentData = array_merge($fragmentData, [
                'vault' => $this->currentSession['vault'] ?? 'default',
                'type' => $this->currentSession['type'] ?? 'log',
                'tags' => array_merge($fragmentData['tags'], $this->currentSession['tags']),
                'metadata' => array_merge([
                    'session_key' => $this->currentSession['session_key'],
                    'session_identifier' => $this->currentSession['identifier'],
                    'session_context' => $this->currentSession['context'] ?? null,
                ], $fragmentData['metadata'] ?? []),

            ]);
        }

        $fragment = Fragment::create($fragmentData);
        $this->chatHistory[] = $fragment->toArray();

        if (str_starts_with($message, ':session end')) {
            $this->endSession();
            return;
        }

        if (str_starts_with($message, ':session show')) {
            $this->showSession();
            return;
        }

        if (str_starts_with($message, ':session')) {
            $this->startSession($message);
            return;
        }


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
            $type = 'todo'; // extracted from $message
            $limit = 5;     // extracted from $message

            $results = \App\Models\Fragment::where('type', $type)
                ->latest()
                ->limit($limit)
                ->get();

            foreach ($results as $fragment) {
                $this->recalledTodos[] = [
                    'id' => $fragment->id,
                    'type' => $fragment->type,
                    'message' => $fragment->message,
                ];
            }

            // $this->chatHistory = array_merge($this->chatHistory, $batch);

            return;
        }


        if (str_starts_with($message, ':recall session')) {
            $this->recallSession($message);
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
- `:recall session {hint}` – Recall all fragments from a saved session (coming soon 🚀)
- `:bookmark` – Bookmark the most recent fragment (or all from chaos)
- `:bookmark list` – List saved bookmarks
- `:bookmark show {hint}` – Replay a bookmark in the chat
- `:bookmark forget {hint}` – Delete a saved bookmark

#### 🔁 Fragments
- `:frag This is something worth remembering` – Log a fragment
- `:chaos This contains multiple items. Do X. Also do Y.` – Split and log a chaos fragment

#### 🧠 Sessions
- `:session vault:work type:meeting Project Sync #weekly` – Start a scoped session for all new fragments
- `:session show` – Display current active session details
- `:session end` – End the current session and return to normal logging

Sessions automatically attach vault, type, tags, and an internal session key to all fragments you create while active.

Example:
`:session vault:writing type:seeds Ideas for Short Stories #shorts`

✅ All fragments during this session will be stored with:
- Vault: `writing`
- Type: `seeds`
- Tags: `shorts`
- Identifier: `Ideas for Short Stories`
- Session grouping key for easy recall later.

#### 🧹 Chat Tools
- `:clear` – Clear the current chat view
- `:help` – Show this help message

---
All fragments are stored and processed. Bookmarks and sessions give you quick access to curated memories and grouped ideas.
MARKDOWN;

    }

    public function toggleTodoCompletion(int $fragmentId)
    {
        $fragment = \App\Models\Fragment::find($fragmentId);

        if (!$fragment) return;

        $state = $fragment->state ?? [];

        if (($state['status'] ?? 'open') === 'complete') {
            $state['status'] = 'open';
        } else {
            $state['status'] = 'complete';
        }

        $fragment->state = $state;
        $fragment->save();
    }

    protected function startSession(string $input)
    {
        $input = trim(str_replace(':session', '', $input)); // Strip ':session'

        $vault = $this->extractVault($input) ?? 'default';
        $type = $this->extractType($input) ?? 'note';
        $tags = $this->extractTags($input);
        $identifier = $this->extractIdentifier($input);

        $this->currentSession = [
            'vault' => $vault,
            'type' => $type,
            'tags' => $tags,
            'identifier' => $identifier,
            'context' => null,
            'session_key' => 'sess_' . \Illuminate\Support\Str::uuid()->toString(),
            'started_at' => now()->toISOString(),
        ];

        $this->chatHistory[] = [
            'type' => 'system',
            'message' => "✅ Session started for vault `{$vault}` and type `{$type}` with tags [" . implode(', ', $tags) . "].",
        ];
    }

    protected function endSession()
    {
        $this->currentSession = null;

        $this->chatHistory[] = [
            'type' => 'system',
            'message' => "🚪 Session ended. Normal fragment logging resumed.",
        ];
    }

    public function showSession()
    {
        if (!$this->currentSession) {
            $this->chatHistory[] = [
                'type' => 'system',
                'message' => "⚡ No active session.",
            ];
            return;
        }

        $tagsString = !empty($this->currentSession['tags']) ? implode(', ', $this->currentSession['tags']) : '(no tags)';

        $this->chatHistory[] = [
            'type' => 'system',
            'message' => <<<TEXT
**Session Active**

- Vault: `{$this->currentSession['vault']}`
- Type: `{$this->currentSession['type']}`
- Tags: `{$tagsString}`
- Identifier: `{$this->currentSession['identifier']}`
- Context: `{$this->currentSession['context']}`
- Started: `{$this->currentSession['started_at']}`
TEXT,
        ];
    }

        protected function extractVault(string $input): ?string
    {
        if (preg_match('/(?:vault:|:vault\s+)(\w+)/i', $input, $match)) {
            return $match[1];
        }
        return null;
    }


    protected function extractType(string $input): ?string
    {
        if (preg_match('/(?:type:|:type\s+)(\w+)/i', $input, $match)) {
            return $match[1];
        }
        return null;
    }


    protected function extractTags(string $input): array
    {
        preg_match_all('/#(\w+)/', $input, $matches);
        return $matches[1] ?? [];
    }

    protected function extractIdentifier(string $input): ?string
    {
        $withoutCommands = preg_replace('/(:vault\s+\w+)|(:type\s+\w+)|(#\w+)/', '', $input);
        return trim($withoutCommands);
    }

    protected function recallSession(string $input)
    {
        $hint = trim(str_replace(':recall session', '', $input));

        if (empty($hint)) {
            $this->chatHistory[] = [
                'type' => 'system',
                'message' => "⚡ Please specify a session identifier hint (e.g., `:recall session Project Sync`).",
            ];
            return;
        }

        $fragments = \App\Models\Fragment::query()
            ->where('metadata->session_identifier', 'like', "%{$hint}%")
            ->orderBy('created_at')
            ->get();

        if ($fragments->isEmpty()) {
            $this->chatHistory[] = [
                'type' => 'system',
                'message' => "⚡ No session found matching `{$hint}`.",
            ];
            return;
        }

        foreach ($fragments as $fragment) {
            $this->recalledTodos[] = [
                'id' => $fragment->id,
                'type' => $fragment->type,
                'message' => $fragment->message,
            ];
        }

        $this->chatHistory[] = [
            'type' => 'system',
            'message' => "✅ Recalled " . $fragments->count() . " fragments from session `{$hint}`.",
        ];
    }



}
