<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Actions\ParseSlashCommand;
use App\Actions\RouteFragment;
use App\Filament\Resources\FragmentResource;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\Project;
use App\Models\Vault;
use App\Services\CommandRegistry;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ChatInterface extends Page
{
    protected static string $resource = FragmentResource::class;

    protected static ?string $slug = 'lens';

    protected static string $view = 'filament.resources.fragment-resource.pages.chat-interface';

    public string $input = '';

    public array $chatMessages = [];

    public array $chatHistory = [];

    public array $commandHistory = [];

    public ?array $currentSession = null;

    public $recalledTodos = [];

    public ?Carbon $lastActivityAt = null;

    public int $sessionTimeoutMinutes = 60; // ← default to 1 hour inactivity

    public ?int $currentChatSessionId = null;

    public array $recentChatSessions = [];

    public array $pinnedChatSessions = [];

    public ?int $currentVaultId = null;

    public ?int $currentProjectId = null;

    public array $vaults = [];

    public array $projects = [];

    public bool $showVaultModal = false;

    public bool $showProjectModal = false;

    public string $newVaultName = '';

    public string $newVaultDescription = '';

    public string $newProjectName = '';

    public string $newProjectDescription = '';

    protected $listeners = [
        'echo:lens.chat,fragment.processed' => 'onFragmentProcessed',
    ];

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public function getLayout(): string
    {
        return 'layouts.chat-interface';
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
        // Initialize vault/project context
        $this->initializeVaultProjectContext();

        // Load recent chat sessions for the sidebar
        $this->loadRecentChatSessions();
        $this->loadPinnedChatSessions();

        // Try to resume the most recent active chat session, or create a new one
        $latestSession = ChatSession::forVaultAndProject($this->currentVaultId, $this->currentProjectId)
            ->recent(1)
            ->first();

        if ($latestSession) {
            $this->currentChatSessionId = $latestSession->id;
            $this->chatMessages = $latestSession->getAttribute('messages') ?? [];
            $this->currentSession = $latestSession->getAttribute('metadata')['currentSession'] ?? null;
        } else {
            // Create a new chat session if none exist
            $this->startNewChat();
        }

        $this->recalledTodos = []; // Fragment IDs for recalled todos
    }

    public function handleInput()
    {
        $message = trim($this->input);
        // ✅ 1. Clear Input Immediately
        $this->input = '';

        $spinnerKey = uniqid('spinner_', true);

        // ✅ 2. Add Temporary "Processing..." Message
        $this->chatMessages[] = [
            'key' => $spinnerKey,
            'type' => 'system',
            'message' => '⏳ Processing...',
        ];

        if (str_starts_with($message, '/')) {
            $command = app(ParseSlashCommand::class)($message);
            $command->arguments['__currentSession'] = $this->currentSession; // Inject current session

            try {
                $handlerClass = CommandRegistry::find($command->command);
                $handler = app($handlerClass);
            } catch (InvalidArgumentException $e) {
                $this->removeSpinner($spinnerKey); // ❗ clean up spinner
                $this->chatMessages[] = [
                    'type' => 'system',
                    'message' => "❌ Command `/{$command->command}` not recognized. Try `/help` for options.",
                ];

                return;
            }

            /** @var \App\DTOs\CommandResponse $response */
            $response = $handler->handle($command);

            $this->removeSpinner($spinnerKey);

            if (! empty($response->shouldResetChat)) {
                $this->chatMessages = [];
            }

            if (! empty($response->message)) {
                $this->chatMessages[] = [
                    'type' => $response->type ?? 'system',
                    'message' => $response->message,
                ];
            }

            if (! empty($response->fragments) && is_array($response->fragments) && array_is_list($response->fragments)) {
                // Handle different fragment types differently
                if ($response->type === 'recall') {
                    // For recall commands, fragments are IDs - store them directly
                    $this->recalledTodos = $response->fragments;
                } else {
                    // For other commands, fragments are arrays with type/message
                    foreach ($response->fragments as $fragment) {
                        if (is_array($fragment) && isset($fragment['type'], $fragment['message'])) {
                            $this->chatMessages[] = [
                                'type' => $fragment['type'],
                                'message' => $fragment['message'],
                            ];
                        }
                    }
                }
            }

            if ($response->type === 'session-start') {
                $this->currentSession = $response->fragments;
            }

            if ($response->type === 'session-end') {
                $this->currentSession = null;
            }

            $this->commandHistory[] = $command->raw;
        } else {
            // normal fragment handling
            $fragment = app(RouteFragment::class)($message);

            $this->removeSpinner($spinnerKey);

            $this->chatMessages[] = [
                'id' => $fragment->id,
                'type' => $fragment->type,
                'message' => $fragment->message,
                'created_at' => $fragment->created_at,
            ];
        }

        // Save the updated chat session after any input
        $this->saveCurrentChatSession();
    }

    protected function removeSpinner(string $spinnerKey): void
    {
        $this->chatMessages = array_filter($this->chatMessages, function ($msg) use ($spinnerKey) {
            return ($msg['key'] ?? null) !== $spinnerKey;
        });

        // Reindex to fix Livewire weirdness
        $this->chatMessages = array_values($this->chatMessages);
    }

    public function getTodoFragments()
    {
        if (empty($this->recalledTodos)) {
            return collect();
        }

        return Fragment::whereIn('id', $this->recalledTodos)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function loadRecentChatSessions(): void
    {
        $this->recentChatSessions = ChatSession::forVaultAndProject($this->currentVaultId, $this->currentProjectId)
            ->where('is_pinned', false)
            ->recent(5)
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'title' => $session->sidebar_title,
                'message_count' => $session->message_count,
                'last_activity' => $this->formatTimestamp($session->last_activity_at) !== 'Just now'
                    ? $this->formatTimestamp($session->last_activity_at)
                    : $this->formatTimestamp($session->updated_at),
                'preview' => $session->last_message_preview,
            ])
            ->toArray();
    }

    public function startNewChat(): void
    {
        // Save current chat session before starting new one
        if ($this->currentChatSessionId) {
            $this->saveCurrentChatSession();
        }

        // Create new chat session with current vault/project context
        $newSession = new ChatSession;
        $newSession->setAttribute('vault_id', $this->currentVaultId);
        $newSession->setAttribute('project_id', $this->currentProjectId);
        $newSession->setAttribute('title', 'New Chat');
        $newSession->setAttribute('messages', []);
        $newSession->setAttribute('metadata', []);
        $newSession->setAttribute('message_count', 0);
        $newSession->setAttribute('last_activity_at', now());
        $newSession->save();

        // Reset chat state
        $this->currentChatSessionId = $newSession->id;
        $this->chatMessages = [];
        $this->currentSession = null;
        $this->recalledTodos = [];

        // Reload recent chat sessions
        $this->loadRecentChatSessions();

        // Add welcome message
        $this->chatMessages[] = [
            'type' => 'system',
            'message' => '💬 **New chat started!** Type your message or use `/help` for commands.',
            'created_at' => now(),
        ];

        $this->saveCurrentChatSession();
    }

    public function switchToChat(int $chatSessionId): void
    {
        // Save current chat session before switching
        if ($this->currentChatSessionId) {
            $this->saveCurrentChatSession();
        }

        // Load the selected chat session
        $chatSession = ChatSession::find($chatSessionId);
        if ($chatSession) {
            $this->currentChatSessionId = $chatSession->id;
            $this->chatMessages = $chatSession->getAttribute('messages') ?? [];
            $this->currentSession = $chatSession->getAttribute('metadata')['currentSession'] ?? null;

            // Update vault/project context if chat belongs to different vault/project
            if ($chatSession->vault_id && $chatSession->vault_id !== $this->currentVaultId) {
                $this->currentVaultId = $chatSession->vault_id;
                $this->loadProjectsForVault($this->currentVaultId);

                // Refresh vault list without reinitializing everything
                $this->vaults = Vault::ordered()->get()->map(fn ($vault) => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                    'description' => $vault->description,
                    'is_default' => $vault->is_default,
                ])->toArray();
            }

            if ($chatSession->project_id && $chatSession->project_id !== $this->currentProjectId) {
                $this->currentProjectId = $chatSession->project_id;
            }

            // Persist the vault/project selection
            $this->persistVaultProjectSelection();

            // Mark as active and update activity time
            $chatSession->update(['last_activity_at' => now()]);
        }

        // Reload recent chat sessions to reflect changes
        $this->loadRecentChatSessions();
    }

    public function saveCurrentChatSession(): void
    {
        if ($this->currentChatSessionId) {
            $chatSession = ChatSession::find($this->currentChatSessionId);
            if ($chatSession) {
                // Use setAttribute to ensure JSON changes are detected
                $chatSession->setAttribute('messages', $this->chatMessages);
                $chatSession->setAttribute('metadata', [
                    'currentSession' => $this->currentSession,
                    'recalledTodos' => $this->recalledTodos,
                ]);
                $chatSession->setAttribute('message_count', count($this->chatMessages));
                $chatSession->setAttribute('last_activity_at', now());
                $chatSession->save();

                $chatSession->updateTitleFromMessages();
            }
        }
    }

    public function injectCommand($command)
    {
        $this->input = $command;
    }

    public function executeCommand($commandName)
    {
        // Set the command input
        $this->input = "/{$commandName}";

        // Execute the command immediately
        $this->handleInput();
    }

    public function showSession()
    {
        if ($this->currentSession) {
            $sessionDetails = "Session Details:\n\n";
            $sessionDetails .= '**Identifier:** '.($this->currentSession['identifier'] ?? 'Unnamed Session')."\n";
            $sessionDetails .= '**Vault:** '.($this->currentSession['vault'] ?? 'N/A')."\n";
            $sessionDetails .= '**Type:** '.($this->currentSession['type'] ?? 'N/A')."\n";

            if (isset($this->currentSession['created_at'])) {
                $sessionDetails .= '**Created:** '.\Carbon\Carbon::parse($this->currentSession['created_at'])->diffForHumans()."\n";
            }

            $this->chatMessages[] = [
                'type' => 'system',
                'message' => $sessionDetails,
            ];
        }
    }

    public function formatTimestamp($timestamp): string
    {
        if (! $timestamp) {
            return 'Just now';
        }

        if (is_string($timestamp)) {
            return Carbon::parse($timestamp)->diffForHumans();
        }

        if ($timestamp instanceof Carbon) {
            return $timestamp->diffForHumans();
        }

        return 'Just now';
    }

    public function onFragmentProcessed($payload)
    {
        $message = "✅ Fragment processed (Origin ID: {$payload['fragmentId']})";

        if (! empty($payload['children'])) {
            $message .= "\n\nFragments created:\n";
            foreach ($payload['children'] as $fragment) {
                $message .= "- [{$fragment['type']}] {$fragment['message']}\n";
            }
        }

        $this->chatMessages[] = [
            'type' => 'system',
            'message' => $message,
        ];
    }

    public function initializeVaultProjectContext(): void
    {
        // Load all vaults and projects
        $this->vaults = Vault::ordered()->get()->map(fn ($vault) => [
            'id' => $vault->id,
            'name' => $vault->name,
            'description' => $vault->description,
            'is_default' => $vault->is_default,
        ])->toArray();

        // Try to restore from session first, then fall back to defaults
        $sessionVaultId = session('seer.current_vault_id');
        $sessionProjectId = session('seer.current_project_id');

        // Check if session vault still exists
        if ($sessionVaultId && Vault::find($sessionVaultId)) {
            $this->currentVaultId = $sessionVaultId;
        } else {
            // Fall back to default vault
            $this->currentVaultId = Vault::getDefault()?->id;
        }

        if ($this->currentVaultId) {
            // Load projects for current vault
            $this->loadProjectsForVault($this->currentVaultId);

            // Check if session project still exists and belongs to current vault
            if ($sessionProjectId) {
                $project = Project::where('id', $sessionProjectId)->where('vault_id', $this->currentVaultId)->first();
                if ($project) {
                    $this->currentProjectId = $sessionProjectId;
                } else {
                    // Fall back to default project for vault
                    $this->currentProjectId = Project::getDefaultForVault($this->currentVaultId)?->id;
                }
            } else {
                // Fall back to default project for vault
                $this->currentProjectId = Project::getDefaultForVault($this->currentVaultId)?->id;
            }
        }

        // Store in session
        $this->persistVaultProjectSelection();
    }

    public function loadProjectsForVault(int $vaultId): void
    {
        $this->projects = Project::forVault($vaultId)->ordered()->get()->map(fn ($project) => [
            'id' => $project->id,
            'vault_id' => $project->vault_id,
            'name' => $project->name,
            'description' => $project->description,
            'is_default' => $project->is_default,
        ])->toArray();
    }

    public function persistVaultProjectSelection(): void
    {
        session([
            'seer.current_vault_id' => $this->currentVaultId,
            'seer.current_project_id' => $this->currentProjectId,
        ]);
    }

    public function switchVault(int $vaultId): void
    {
        $this->currentVaultId = $vaultId;
        $this->loadProjectsForVault($vaultId);

        // Set default project for new vault
        $this->currentProjectId = Project::getDefaultForVault($vaultId)?->id;

        // Persist selection in session
        $this->persistVaultProjectSelection();

        // Refresh chat sessions
        $this->loadRecentChatSessions();
        $this->loadPinnedChatSessions();

        // Switch to most recent chat in new context or create new one
        $this->switchToContextChat();
    }

    public function switchProject(int $projectId): void
    {
        $this->currentProjectId = $projectId;

        // Persist selection in session
        $this->persistVaultProjectSelection();

        // Refresh chat sessions
        $this->loadRecentChatSessions();
        $this->loadPinnedChatSessions();

        // Switch to most recent chat in new context or create new one
        $this->switchToContextChat();
    }

    public function switchToContextChat(): void
    {
        $latestSession = ChatSession::forVaultAndProject($this->currentVaultId, $this->currentProjectId)
            ->recent(1)
            ->first();

        if ($latestSession) {
            $this->switchToChat($latestSession->id);
        } else {
            $this->startNewChat();
        }
    }

    public function loadPinnedChatSessions(): void
    {
        $this->pinnedChatSessions = ChatSession::forVaultAndProject($this->currentVaultId, $this->currentProjectId)
            ->pinned()
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'title' => $session->sidebar_title,
                'message_count' => $session->message_count,
                'last_activity' => $this->formatTimestamp($session->last_activity_at) !== 'Just now'
                    ? $this->formatTimestamp($session->last_activity_at)
                    : $this->formatTimestamp($session->updated_at),
                'preview' => $session->last_message_preview,
                'is_pinned' => $session->is_pinned,
                'sort_order' => $session->sort_order,
            ])
            ->toArray();
    }

    public function togglePinChat(int $chatSessionId): void
    {
        $session = ChatSession::find($chatSessionId);
        if ($session) {
            $session->togglePin();
            $this->loadPinnedChatSessions();
            $this->loadRecentChatSessions();
        }
    }

    public function openVaultModal(): void
    {
        $this->newVaultName = '';
        $this->newVaultDescription = '';
        $this->showVaultModal = true;
    }

    public function closeVaultModal(): void
    {
        $this->showVaultModal = false;
        $this->newVaultName = '';
        $this->newVaultDescription = '';
    }

    public function openProjectModal(): void
    {
        $this->newProjectName = '';
        $this->newProjectDescription = '';
        $this->showProjectModal = true;
    }

    public function closeProjectModal(): void
    {
        $this->showProjectModal = false;
        $this->newProjectName = '';
        $this->newProjectDescription = '';
    }

    public function createNewVault(): void
    {
        // Validate required fields
        if (empty(trim($this->newVaultName))) {
            return;
        }

        $vault = Vault::create([
            'name' => trim($this->newVaultName),
            'description' => trim($this->newVaultDescription) ?: 'Created from interface',
            'sort_order' => (Vault::max('sort_order') ?? 0) + 1,
        ]);

        // Create default project for new vault
        Project::create([
            'vault_id' => $vault->id,
            'name' => 'Default Project',
            'description' => 'Default project for this vault',
            'is_default' => true,
            'sort_order' => 0,
        ]);

        // Refresh vault list and switch to new vault
        $this->vaults = Vault::ordered()->get()->map(fn ($vault) => [
            'id' => $vault->id,
            'name' => $vault->name,
            'description' => $vault->description,
            'is_default' => $vault->is_default,
        ])->toArray();

        $this->currentVaultId = $vault->id;
        $this->loadProjectsForVault($vault->id);
        $this->currentProjectId = Project::getDefaultForVault($vault->id)?->id;
        $this->persistVaultProjectSelection();

        // Refresh chat sessions and switch to context
        $this->loadRecentChatSessions();
        $this->loadPinnedChatSessions();
        $this->switchToContextChat();

        // Close modal
        $this->closeVaultModal();
    }

    public function createNewProject(): void
    {
        // Validate required fields
        if (empty(trim($this->newProjectName))) {
            return;
        }

        $project = Project::create([
            'vault_id' => $this->currentVaultId,
            'name' => trim($this->newProjectName),
            'description' => trim($this->newProjectDescription) ?: 'Created from interface',
            'sort_order' => (Project::forVault($this->currentVaultId)->max('sort_order') ?? 0) + 1,
        ]);

        // Reload projects and switch to new one
        $this->loadProjectsForVault($this->currentVaultId);
        $this->currentProjectId = $project->id;
        $this->persistVaultProjectSelection();

        // Refresh chat sessions and switch to context
        $this->loadRecentChatSessions();
        $this->loadPinnedChatSessions();
        $this->switchToContextChat();

        // Close modal
        $this->closeProjectModal();
    }

    public function updatePinnedChatOrder(array $newOrder): void
    {
        foreach ($newOrder as $item) {
            ChatSession::where('id', $item['id'])
                ->update(['sort_order' => $item['sortOrder']]);
        }

        // Refresh pinned chat sessions
        $this->loadPinnedChatSessions();
    }

    public function updatedCurrentVaultId($vaultId): void
    {
        $this->switchVault($vaultId);
    }

    public function updatedCurrentProjectId($projectId): void
    {
        $this->switchProject($projectId);
    }

    public function debugState(): void
    {
        $this->chatMessages[] = [
            'type' => 'system',
            'message' => "**Debug State:**\n".
                'Current Vault ID: '.($this->currentVaultId ?: 'null')."\n".
                'Current Project ID: '.($this->currentProjectId ?: 'null')."\n".
                'Vaults count: '.count($this->vaults)."\n".
                'Projects count: '.count($this->projects)."\n".
                'Session Vault: '.(session('seer.current_vault_id') ?: 'null')."\n".
                'Session Project: '.(session('seer.current_project_id') ?: 'null'),
        ];
    }
}
