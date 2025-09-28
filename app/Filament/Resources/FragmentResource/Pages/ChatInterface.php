<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Actions\LogRecallDecision;
use App\Actions\ParseSearchGrammar;
use App\Actions\ParseSlashCommand;
use App\Actions\RouteFragment;
use App\Actions\SearchFragments;
use App\Filament\Resources\FragmentResource;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\Project;
use App\Models\Type;
use App\Models\Vault;
use App\Services\CommandRegistry;
use App\Services\ToastService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Livewire\Attributes\On;

class ChatInterface extends Page
{
    protected static string $resource = FragmentResource::class;

    protected static ?string $slug = 'lens';

    protected string $view = 'filament.resources.fragment-resource.pages.chat-interface';

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

    // Recall palette state
    public bool $showRecallPalette = false;

    // Command injection state
    public bool $inCommandMode = false;

    public array $originalFragments = [];

    public string $recallQuery = '';

    public array $recallResults = [];

    public array $recallSuggestions = [];

    public array $recallAutocomplete = [];

    public bool $recallLoading = false;

    public int $selectedRecallIndex = 0;

    public array $vaults = [];

    public array $projects = [];

    public bool $showVaultModal = false;

    public bool $showProjectModal = false;

    public string $newVaultName = '';

    public string $newVaultDescription = '';

    public string $newProjectName = '';

    public string $newProjectDescription = '';

    public bool $showToastSettings = false;

    protected $listeners = [
        'echo:lens.chat,fragment.processed' => 'onFragmentProcessed',
        'undo-fragment' => 'handleUndoDeleteObject',
        'join-channel' => 'handleJoinChannel',
    ];

    protected ToastService $toastService;

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
        // Initialize services
        $this->toastService = app(ToastService::class);

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
            $rawMessages = $latestSession->getAttribute('messages') ?? [];

            // Filter out messages with invalid fragment IDs
            $this->chatMessages = array_filter($rawMessages, function ($msg) {
                // Keep system messages and messages without IDs
                if (! isset($msg['id']) || empty($msg['id'])) {
                    return true;
                }

                // Verify fragment still exists (including soft deleted)
                return Fragment::withTrashed()->where('id', $msg['id'])->exists();
            });

            // Reindex array
            $this->chatMessages = array_values($this->chatMessages);

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

        // Early return for empty messages - fail silently
        if (empty($message)) {
            $this->input = '';

            return;
        }

        // ✅ 1. Clear Input Immediately
        $this->input = '';

        $spinnerKey = uniqid('spinner_', true);

        // ✅ 2. Add Temporary "Processing..." Message
        $this->chatMessages[] = [
            'key' => $spinnerKey,
            'type' => 'system',
            'type_id' => $this->getSystemTypeId(),
            'message' => '⏳ Processing...',
        ];

        if (str_starts_with($message, '/')) {
            $command = app(ParseSlashCommand::class)($message);
            $command->arguments['__currentSession'] = $this->currentSession; // Inject current session
            $command->arguments['current_chat_session_id'] = $this->currentChatSessionId; // Inject current chat session ID
            $command->arguments['vault_id'] = $this->currentVaultId; // Inject vault context
            $command->arguments['project_id'] = $this->currentProjectId; // Inject project context

            try {
                $handlerClass = CommandRegistry::find($command->command);
                $handler = app($handlerClass);
            } catch (InvalidArgumentException $e) {
                $this->removeSpinner($spinnerKey); // ❗ clean up spinner

                // Show error toast instead of adding to chat
                $this->showErrorToast("Command `/{$command->command}` not recognized. Try `/help` for options.");

                return;
            }

            /** @var \App\DTOs\CommandResponse $response */
            $response = $handler->handle($command);

            $this->removeSpinner($spinnerKey);

            // Handle command injection
            if ($response->shouldOpenPanel && isset($response->panelData)) {
                $this->enterCommandMode();
                $this->injectCommandResults($response);

                return;
            }

            // Handle name command response
            if ($response->type === 'name-success') {
                // Refresh the sidebar to show the new channel name
                $this->loadRecentChatSessions();
                $this->loadPinnedChatSessions();

                // Show success toast
                if ($response->shouldShowSuccessToast && ! empty($response->toastData)) {
                    $this->showSuccessToast(
                        $response->toastData['title'] ?? 'Success',
                        $response->toastData['message'] ?? '',
                        $response->toastData['fragmentType'] ?? 'fragment',
                        $response->toastData['fragmentId'] ?? null
                    );
                }

                return;
            }

            // Handle join command response
            if ($response->type === 'join-success' && isset($response->data['action']) && $response->data['action'] === 'switch_chat') {
                $chatSessionId = $response->data['chat_session_id'];

                // Exit command mode if we're in it (e.g., after /help join)
                if ($this->inCommandMode) {
                    $this->exitCommandMode();
                }

                $this->switchToChat($chatSessionId);

                // Show success toast
                if ($response->shouldShowSuccessToast && ! empty($response->toastData)) {
                    $this->showSuccessToast(
                        $response->toastData['title'] ?? 'Success',
                        $response->toastData['message'] ?? '',
                        $response->toastData['fragmentType'] ?? 'fragment',
                        $response->toastData['fragmentId'] ?? null
                    );
                }

                return;
            }

            // Handle success toast notifications (for /frag and other commands)
            if ($response->shouldShowSuccessToast && ! empty($response->toastData)) {
                $this->showSuccessToast(
                    $response->toastData['title'] ?? 'Success',
                    $response->toastData['message'] ?? '',
                    $response->toastData['fragmentType'] ?? 'fragment',
                    $response->toastData['fragmentId'] ?? null
                );

                return;
            }

            // Handle error toast notifications
            if ($response->shouldShowErrorToast) {
                $this->showErrorToast($response->message ?? 'An error occurred.');

                return;
            }

            // Handle success toast notifications
            if ($response->shouldShowSuccessToast) {
                $this->showSuccessToast(
                    'Success',
                    $response->message ?? 'Command completed successfully.',
                    'fragment',
                    null
                );

                return;
            }

            // Handle clear command (exit command mode)
            if ($response->type === 'clear') {
                if ($this->inCommandMode) {
                    $this->exitCommandMode();
                }
                // Clear the input field to prevent modal from showing /clear
                $this->input = '';

                // No message added - just exit silently
                return;
            }

            // Legacy behavior for commands that haven't been updated yet
            if (! empty($response->shouldResetChat)) {
                $this->chatMessages = [];
            }

            // Don't add system messages to chat anymore
            // Commands should use shouldOpenPanel for feedback

            if (! empty($response->fragments) && is_array($response->fragments) && array_is_list($response->fragments)) {
                // Handle different fragment types differently
                if ($response->type === 'recall') {
                    // For recall commands, fragments are IDs - store them directly
                    $this->recalledTodos = $response->fragments;
                } else {
                    // For other commands, fragments are arrays with type/message
                    foreach ($response->fragments as $fragment) {
                        if (is_array($fragment) && isset($fragment['type'], $fragment['message'])) {
                            $fragmentType = is_array($fragment['type'])
                                ? ($fragment['type']['value'] ?? $fragment['type']['label'] ?? 'log')
                                : $fragment['type'];

                            $this->chatMessages[] = [
                                'type' => $fragmentType ?? 'log',
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
                'type' => $fragment->type?->value ?? 'log',
                'type_id' => $fragment->type_id,
                'message' => $fragment->message,
                'created_at' => $fragment->created_at,
            ];
        }

        // Save the updated chat session after any input
        $this->saveCurrentChatSession();

        // Refresh recent chat sessions to update count badges
        $this->loadRecentChatSessions();
    }

    protected function getSystemTypeId(): int
    {
        $systemType = Type::where('value', 'system')->first();
        if ($systemType) {
            return $systemType->id;
        }

        $logType = Type::where('value', 'log')->first();
        if ($logType) {
            return $logType->id;
        }

        // Fallback: return the first available type or create a default one
        $firstType = Type::first();
        if ($firstType) {
            return $firstType->id;
        }

        // Last resort: create a system type
        return Type::create(['value' => 'system', 'name' => 'System'])->id;
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
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('updated_at', 'desc') // Fallback for items without sort_order
            ->limit(5)
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'title' => $session->sidebar_title,
                'short_code' => $session->short_code,
                'channel_display' => $session->channel_sidebar_display,
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

        // No welcome message - clean start
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

        // Don't reload chat sessions when switching - sidebar should stay stable
    }

    public function saveCurrentChatSession(): void
    {
        if ($this->currentChatSessionId) {
            $chatSession = ChatSession::find($this->currentChatSessionId);
            if ($chatSession) {
                // Use the original chat messages if in command mode, otherwise use current messages
                $messagesToSave = $this->inCommandMode ? $this->originalFragments : $this->chatMessages;
                $messageCount = count($messagesToSave);

                // Use setAttribute to ensure JSON changes are detected
                $chatSession->setAttribute('messages', $messagesToSave);
                $chatSession->setAttribute('metadata', [
                    'currentSession' => $this->currentSession,
                    'recalledTodos' => $this->recalledTodos,
                ]);
                $chatSession->setAttribute('message_count', $messageCount);
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
                'type_id' => $this->getSystemTypeId(),
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
            'type_id' => $this->getSystemTypeId(),
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
                'short_code' => $session->short_code,
                'channel_display' => $session->channel_sidebar_display,
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

    public function deleteChat(int $chatSessionId): void
    {
        $session = ChatSession::find($chatSessionId);
        if ($session) {
            // Store session info for undo
            $sessionTitle = $session->title ?? 'Untitled Chat';
            $sessionMessage = "Chat \"{$sessionTitle}\" deleted";

            // Soft delete the chat session
            $session->delete();

            // If we deleted the current chat, switch to a new one
            if ($this->currentChatSessionId === $chatSessionId) {
                $this->startNewChat();
            }

            // Reload the chat lists
            $this->loadPinnedChatSessions();
            $this->loadRecentChatSessions();

            // Use a special prefix for chat sessions
            $undoId = 'chat-'.$chatSessionId;

            // Trigger undo toast using the same pattern as fragment deletion
            $this->dispatch('show-undo-toast',
                fragmentId: $undoId,
                message: $sessionMessage,
                objectType: 'chat'
            );

            // Also dispatch a browser event as fallback
            $this->js("
                window.dispatchEvent(new CustomEvent('show-undo-toast', {
                    detail: { fragmentId: '{$undoId}', message: ".json_encode($sessionMessage).", objectType: 'chat' }
                }));
            ");
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
            'type_id' => $this->getSystemTypeId(),
            'message' => "**Debug State:**\n".
                'Current Vault ID: '.($this->currentVaultId ?: 'null')."\n".
                'Current Project ID: '.($this->currentProjectId ?: 'null')."\n".
                'Vaults count: '.count($this->vaults)."\n".
                'Projects count: '.count($this->projects)."\n".
                'Session Vault: '.(session('seer.current_vault_id') ?: 'null')."\n".
                'Session Project: '.(session('seer.current_project_id') ?: 'null'),
        ];
    }

    public function deleteFragment(int $fragmentId): void
    {
        Log::debug('Attempting to delete fragment', ['fragment_id' => $fragmentId]);

        $fragment = Fragment::with('type')->find($fragmentId);

        if (! $fragment) {
            Log::warning('Fragment not found for deletion', ['fragment_id' => $fragmentId]);
            $this->dispatch('show-error-toast', [
                'message' => "Fragment not found (ID: {$fragmentId})",
            ]);

            return;
        }

        Log::debug('Fragment found, proceeding with soft delete', [
            'fragment_id' => $fragmentId,
            'message' => $fragment->message,
            'type' => $fragment->type?->value ?? $fragment->getAttribute('type'),
        ]);

        // Store message before deletion for toast
        $fragmentMessage = $fragment->message;

        // Find bookmarks that contain this fragment and store them for potential restoration
        $bookmarksContaining = \App\Models\Bookmark::whereJsonContains('fragment_ids', $fragmentId)->get();
        $bookmarkIds = $bookmarksContaining->pluck('id')->toArray();

        // Store bookmark IDs in fragment metadata for restoration
        $fragment->update([
            'metadata' => array_merge($fragment->metadata ?? [], [
                'deleted_bookmark_ids' => $bookmarkIds,
            ]),
        ]);

        // Remove fragment from all bookmarks but don't delete empty ones
        foreach ($bookmarksContaining as $bookmark) {
            $fragmentIds = $bookmark->fragment_ids;
            $updatedFragmentIds = array_values(array_filter($fragmentIds, fn ($id) => $id !== $fragmentId));

            // Always update the bookmark, even if it becomes empty
            // This preserves the bookmark for restoration
            $bookmark->update(['fragment_ids' => $updatedFragmentIds]);
        }

        Log::debug('Removed fragment from bookmarks', [
            'fragment_id' => $fragmentId,
            'affected_bookmarks' => count($bookmarksContaining),
            'stored_bookmark_ids' => $bookmarkIds,
        ]);

        // Soft delete the fragment
        $fragment->delete();

        // Remove from chat messages display
        $this->chatMessages = array_filter($this->chatMessages, function ($msg) use ($fragmentId) {
            return ($msg['id'] ?? null) !== $fragmentId;
        });

        // Reindex the array to avoid gaps
        $this->chatMessages = array_values($this->chatMessages);

        // Save the updated chat session
        $this->saveCurrentChatSession();

        // Trigger undo toast
        $this->dispatch('show-undo-toast',
            fragmentId: $fragmentId,
            message: $fragmentMessage
        );

        // Dispatch bookmark-toggled event to update UI if fragment was bookmarked
        if (! empty($bookmarkIds)) {
            $this->js("
                window.dispatchEvent(new CustomEvent('bookmark-toggled', {
                    detail: { fragmentId: {$fragmentId}, action: 'removed', isBookmarked: false }
                }));
            ");
        }

        // Also dispatch a browser event as fallback
        $this->js("
            window.dispatchEvent(new CustomEvent('show-undo-toast', {
                detail: { fragmentId: {$fragmentId}, message: ".json_encode($fragmentMessage).' }
            }));
        ');

        Log::info('Fragment soft deleted successfully', [
            'fragment_id' => $fragmentId,
            'message' => $fragmentMessage,
        ]);
    }

    public function undoFragmentDelete(int $fragmentId): void
    {
        // Check if fragment had bookmarks before restoration
        $fragmentWithTrashed = Fragment::withTrashed()->find($fragmentId);
        $hadBookmarks = ! empty($fragmentWithTrashed->metadata['deleted_bookmark_ids'] ?? []);

        $restoredFragment = app(\App\Actions\UndoFragmentDelete::class)($fragmentId);

        if ($restoredFragment) {
            // Add back to chat messages
            $this->chatMessages[] = [
                'id' => $restoredFragment->id,
                'type' => $restoredFragment->type?->value ?? 'log',
                'message' => $restoredFragment->message,
                'created_at' => $restoredFragment->created_at,
            ];

            // Save the updated chat session
            $this->saveCurrentChatSession();

            // Dispatch bookmark update event if fragment was restored to bookmarks
            if ($hadBookmarks) {
                $this->js("
                    window.dispatchEvent(new CustomEvent('bookmark-toggled', {
                        detail: { fragmentId: {$restoredFragment->id}, action: 'added', isBookmarked: true }
                    }));
                ");
            }

            // Dispatch event to refresh bookmark widget
            $this->js("
                window.dispatchEvent(new CustomEvent('fragment-restored', {
                    detail: { fragmentId: {$restoredFragment->id} }
                }));
            ");

            // Show success toast instead of chat message
            $this->dispatch('show-success-toast', [
                'message' => 'Fragment restored successfully',
                'objectType' => 'fragment',
            ]);

            // Also dispatch browser event as fallback
            $this->js("
                window.dispatchEvent(new CustomEvent('show-success-toast', {
                    detail: { message: 'Fragment restored successfully', objectType: 'fragment' }
                }));
            ");
        } else {
            // Show error toast for failed restoration
            $this->dispatch('show-success-toast', [
                'message' => 'Could not restore fragment - undo window expired or fragment not found',
                'objectType' => 'fragment',
            ]);

            $this->js("
                window.dispatchEvent(new CustomEvent('show-success-toast', {
                    detail: { message: 'Could not restore fragment - undo window expired or fragment not found', objectType: 'fragment' }
                }));
            ");
        }
    }

    public function handleUndoDeleteObject(...$args): void
    {
        Log::debug('Undo delete object args received', ['args' => $args]);

        $objectId = null;

        // Handle different parameter formats
        if (count($args) === 1) {
            $arg = $args[0];
            if (is_array($arg) && isset($arg['fragmentId'])) {
                $objectId = $arg['fragmentId'];
            } elseif (is_numeric($arg) || is_string($arg)) {
                $objectId = $arg;
            }
        } elseif (count($args) > 1) {
            $objectId = $args[0];
        }

        if ($objectId) {
            // Determine object type for logging
            $objectType = (is_string($objectId) && str_starts_with($objectId, 'chat-')) ? 'chat' : 'fragment';
            Log::debug('Handling undo delete event', [
                'object_id' => $objectId,
                'object_type' => $objectType,
            ]);

            // Check if this is a chat session undo (prefixed with 'chat-')
            if ($objectType === 'chat') {
                $chatSessionId = (int) str_replace('chat-', '', $objectId);
                $session = ChatSession::withTrashed()->find($chatSessionId);
                if ($session) {
                    $session->restore();

                    // Reload the chat lists
                    $this->loadPinnedChatSessions();
                    $this->loadRecentChatSessions();

                    Log::debug('Chat session restored successfully', [
                        'chat_session_id' => $chatSessionId,
                        'session_title' => $session->title,
                    ]);

                    // Show success toast for chat restoration
                    $this->dispatch('show-success-toast', [
                        'message' => "Chat \"{$session->title}\" restored successfully",
                        'objectType' => 'chat',
                    ]);

                    $this->js("
                        window.dispatchEvent(new CustomEvent('show-success-toast', {
                            detail: { message: 'Chat \"{$session->title}\" restored successfully', objectType: 'chat' }
                        }));
                    ");
                } else {
                    Log::warning('Chat session not found for restoration', ['chat_session_id' => $chatSessionId]);
                }
            } else {
                // Handle regular fragment undo
                Log::debug('Processing fragment undo', ['fragment_id' => $objectId]);
                $this->undoFragmentDelete((int) $objectId);
            }
        } else {
            Log::warning('No valid object ID received in undo event', ['args' => $args]);
        }
    }

    protected function addChatMessage(string $type, string $message): void
    {
        $this->chatMessages[] = [
            'type' => $type,
            'message' => $message,
            'created_at' => now(),
        ];
    }

    // ===== RECALL PALETTE METHODS =====

    public function openRecallPalette(): void
    {
        $this->showRecallPalette = true;
        $this->recallQuery = '';
        $this->recallResults = [];
        $this->selectedRecallIndex = 0;
        $this->loadRecallSuggestions();
    }

    public function closeRecallPalette(bool $logDismissal = true): void
    {
        // Log dismissal if requested and we have query/results
        if ($logDismissal && ! empty($this->recallQuery) && ! empty($this->recallResults)) {
            $logDecision = app(LogRecallDecision::class);
            $logDecision(
                query: $this->recallQuery,
                results: $this->recallResults,
                selectedFragment: null,
                selectedIndex: null,
                action: 'dismiss'
            );
        }

        $this->showRecallPalette = false;
        $this->recallQuery = '';
        $this->selectedRecallIndex = 0;
        $this->recallLoading = false;

        if ($logDismissal) {
            $this->recallResults = [];
        }

        $this->recallSuggestions = [];
        $this->recallAutocomplete = [];
    }

    public function updatedRecallQuery(): void
    {
        if (strlen($this->recallQuery) >= 2) {
            $this->performRecallSearch();
        } else {
            $this->recallResults = [];
            $this->loadRecallSuggestions();
        }
        $this->selectedRecallIndex = 0;
    }

    public function performRecallSearch(): void
    {
        $this->recallLoading = true;

        try {
            // Use hybrid search if available and query is simple (no advanced filters)
            $useHybrid = $this->shouldUseHybridSearch($this->recallQuery);

            if ($useHybrid) {
                // Use the new hybrid search for better results
                $this->performHybridSearch();
            } else {
                // Fall back to standard search for complex queries with filters
                $this->performStandardSearch();
            }

        } catch (\Exception $e) {
            Log::error('Recall search failed', [
                'query' => $this->recallQuery,
                'error' => $e->getMessage(),
            ]);
            $this->recallResults = [];
            $this->recallLoading = false;
        }
    }

    protected function shouldUseHybridSearch(string $query): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        // Use hybrid search for simple queries without advanced filters
        // Advanced filters are things like type:, has:, @mentions, etc.
        $hasAdvancedFilters = preg_match('/\b(type:|has:|@|#|vault:|project:)/', $query);

        // Also check if embeddings are configured
        $embeddingsConfigured = config('fragments.embeddings.provider') !== null;

        return ! $hasAdvancedFilters && $embeddingsConfigured && strlen($query) >= 2;
    }

    protected function performHybridSearch(): void
    {
        try {
            // Call the hybrid search API endpoint
            $httpClient = \Illuminate\Support\Facades\Http::baseUrl(url('/'));

            // Disable SSL verification in local environment
            if (app()->environment('local')) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->get('api/search/hybrid', [
                'q' => $this->recallQuery,
                'limit' => 10,
                'provider' => config('fragments.embeddings.provider'),
            ]);

            if ($response->successful()) {
                $results = $response->json();

                // Format hybrid search results for display
                $this->recallResults = collect($results)->map(function ($result) {
                    // Load the full fragment to get additional data
                    $fragment = Fragment::find($result['id']);

                    if (! $fragment) {
                        return null;
                    }

                    return [
                        'id' => $fragment->id,
                        'type' => $fragment->type instanceof \App\Models\Type ? $fragment->type->value : ($fragment->type instanceof \BackedEnum ? $fragment->type->value : $fragment->type),
                        'title' => $result['title'] ?: $this->truncateText($fragment->message, 80),
                        'message' => $fragment->message,
                        'created_at' => $fragment->created_at->diffForHumans(),
                        'tags' => $fragment->tags ?? [],
                        'search_score' => $result['score'] ?? 0,
                        'preview' => strip_tags($result['snippet'] ?? $this->truncateText($fragment->message, 120)),
                        'snippet' => $result['snippet'] ?? null,
                        'vec_sim' => $result['vec_sim'] ?? 0,
                        'txt_rank' => $result['txt_rank'] ?? 0,
                    ];
                })->filter()->values()->toArray();

                // Clear suggestions for hybrid search
                $this->recallSuggestions = [];
                $this->recallAutocomplete = [];

            } else {
                // Fall back to standard search on API failure
                $this->performStandardSearch();
            }
        } catch (\Exception $e) {
            Log::error('Hybrid search failed', [
                'query' => $this->recallQuery,
                'error' => $e->getMessage(),
            ]);

            // Fall back to standard search
            $this->performStandardSearch();
        }

        $this->recallLoading = false;
    }

    protected function performStandardSearch(): void
    {
        try {
            // Parse the search query
            $grammarParser = app(ParseSearchGrammar::class);
            $parsedGrammar = $grammarParser($this->recallQuery);

            // Perform the search
            $searchAction = app(SearchFragments::class);

            // Get the vault name for searching
            $vaultName = null;
            $projectId = $this->currentProjectId;

            if (! app()->runningUnitTests()) {
                if ($this->currentVaultId) {
                    $vault = \App\Models\Vault::find($this->currentVaultId);
                    $vaultName = $vault?->name;
                }
            } else {
                $projectId = null;
            }

            $results = $searchAction(
                query: $this->recallQuery,
                vault: $vaultName,
                projectId: $projectId,
                sessionId: $this->currentChatSessionId ? "session-{$this->currentChatSessionId}" : null,
                limit: 10
            );

            // Format results for display
            $this->recallResults = $results->map(function ($fragment) {
                return [
                    'id' => $fragment->id,
                    'type' => $fragment->type instanceof \App\Models\Type ? $fragment->type->value : ($fragment->type instanceof \BackedEnum ? $fragment->type->value : $fragment->type),
                    'title' => $fragment->title ?: $this->truncateText($fragment->message, 80),
                    'message' => $fragment->message,
                    'created_at' => $fragment->created_at->diffForHumans(),
                    'tags' => $fragment->tags ?? [],
                    'search_score' => $fragment->search_score ?? 0,
                    'preview' => $this->truncateText($fragment->message, 120),
                ];
            })->toArray();

            // Update suggestions and autocomplete
            $this->recallSuggestions = $parsedGrammar['suggestions'];
            $this->recallAutocomplete = $parsedGrammar['autocomplete'];

        } catch (\Exception $e) {
            Log::error('Standard search failed', [
                'query' => $this->recallQuery,
                'error' => $e->getMessage(),
            ]);
            $this->recallResults = [];
        }

        $this->recallLoading = false;
    }

    public function loadRecallSuggestions(): void
    {
        // Load default suggestions when no search query
        $grammarParser = app(ParseSearchGrammar::class);
        $parsed = $grammarParser('');
        $this->recallSuggestions = $parsed['suggestions'];
        $this->recallAutocomplete = $parsed['autocomplete'];
    }

    public function selectRecallResult(int $index): void
    {
        if (! isset($this->recallResults[$index])) {
            return;
        }

        $fragment = $this->recallResults[$index];

        // Log the recall decision for analytics
        $logDecision = app(LogRecallDecision::class);
        if (isset($fragment['id'])) {
            $selectedFragment = Fragment::find($fragment['id']);
            $logDecision(
                query: $this->recallQuery,
                results: $this->recallResults,
                selectedFragment: $selectedFragment,
                selectedIndex: $index,
                action: 'select'
            );
        }

        // Add the recalled fragment to chat
        $this->addRecallResultToChat($fragment);

        // Close the palette (without logging dismissal since we already logged selection)
        $this->closeRecallPalette(false);
    }

    public function selectCurrentRecallResult(): void
    {
        // Don't select if still loading
        if ($this->recallLoading) {
            return;
        }

        // Only select if we have results - DON'T close palette if no results
        if (count($this->recallResults) > 0) {
            $this->selectRecallResult($this->selectedRecallIndex);
        }
    }

    public function moveRecallSelection(string $direction): void
    {
        $maxIndex = max(0, count($this->recallResults) - 1);

        if ($direction === 'up') {
            $this->selectedRecallIndex = max(0, $this->selectedRecallIndex - 1);
        } elseif ($direction === 'down') {
            $this->selectedRecallIndex = min($maxIndex, $this->selectedRecallIndex + 1);
        }
    }

    public function applySuggestion(array $suggestion): void
    {
        if ($suggestion['type'] === 'filter') {
            // Append filter to query
            $this->recallQuery = trim($this->recallQuery.' '.$suggestion['text']);
            $this->performRecallSearch();
        }
    }

    public function applyAutocomplete(array $autocomplete): void
    {
        // Replace or append autocomplete value
        $this->recallQuery = $autocomplete['value'];
        $this->performRecallSearch();
    }

    private function addRecallResultToChat(array $fragment): void
    {
        // Add system message showing the recalled fragment
        $this->chatMessages[] = [
            'type' => 'recall',
            'message' => "🔍 **Recalled:** [{$fragment['type']}] {$fragment['title']}",
            'created_at' => now(),
            'recalled_fragment' => $fragment,
        ];

        // Save the updated chat session
        $this->saveCurrentChatSession();
    }

    private function truncateText(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length).'...' : $text;
    }

    // Command Injection Methods

    private function enterCommandMode(): void
    {
        // Only back up original chat messages if not already in command mode
        // This preserves the true original chat when switching between commands
        if (! $this->inCommandMode) {
            $this->originalFragments = $this->chatMessages;
        }
        $this->inCommandMode = true;
    }

    private function injectCommandResults(\App\DTOs\CommandResponse $response): void
    {
        // Create a single "fragment" representing the command result
        $commandFragment = [
            'type' => 'command_result',
            'command_type' => $response->type,
            'data' => $response->panelData,
            'message' => $response->message ?? null,
        ];

        // Replace chat messages with just this command result
        $this->chatMessages = [$commandFragment];
    }

    public function exitCommandMode(): void
    {
        if ($this->inCommandMode) {
            // Restore original chat messages
            $this->chatMessages = $this->originalFragments;
            $this->originalFragments = [];
            $this->inCommandMode = false;

            // Refresh recent chats to ensure count badges are correct
            $this->loadRecentChatSessions();
        }
    }

    private function showSuccessToast(string $title, string $message, string $fragmentType = 'fragment', ?int $fragmentId = null): void
    {
        $user = auth()->user();
        $severity = ToastService::SEVERITY_SUCCESS;

        // Ensure we have a valid user instance
        if ($user && ! ($user instanceof \App\Models\User)) {
            $user = null;
        }

        // Check if this toast should be shown based on user verbosity preference
        if (! $this->toastService->shouldShowToast($severity, $user)) {
            return;
        }

        // Check for duplicates (only for success toasts to reduce noise)
        if ($this->toastService->isDuplicate($severity, $message, $user)) {
            return;
        }

        // Dispatch browser event to show success toast
        $this->dispatch('show-success-toast', [
            'title' => $title,
            'message' => $message,
            'fragmentType' => $fragmentType,
            'fragmentId' => $fragmentId,
        ]);
    }

    private function showErrorToast(string $message): void
    {
        $user = auth()->user();
        $severity = ToastService::SEVERITY_ERROR;

        // Ensure we have a valid user instance
        if ($user && ! ($user instanceof \App\Models\User)) {
            $user = null;
        }

        // Check if this toast should be shown based on user verbosity preference
        if (! $this->toastService->shouldShowToast($severity, $user)) {
            return;
        }

        // Error toasts are not subject to duplicate suppression (important to show all errors)

        // Dispatch browser event to show error toast
        $this->dispatch('show-error-toast', [
            'message' => $message,
        ]);
    }

    public function updateToastVerbosity(string $verbosity): void
    {
        if (! in_array($verbosity, [ToastService::VERBOSITY_MINIMAL, ToastService::VERBOSITY_NORMAL, ToastService::VERBOSITY_VERBOSE])) {
            return;
        }

        $user = auth()->user();
        if ($user && ($user instanceof \App\Models\User)) {
            $user->update(['toast_verbosity' => $verbosity]);

            // Show confirmation (but respect the new setting)
            if ($verbosity !== ToastService::VERBOSITY_MINIMAL) {
                $this->showSuccessToast(
                    'Settings Updated',
                    'Toast notification preference has been saved.',
                    'setting',
                    null
                );
            }
        }

        $this->showToastSettings = false;
    }

    public function toggleToastSettings(): void
    {
        $this->showToastSettings = ! $this->showToastSettings;
    }

    public function getCurrentToastVerbosity(): string
    {
        return auth()->user()?->toast_verbosity ?? ToastService::VERBOSITY_NORMAL;
    }

    public function getToastVerbosityOptions(): array
    {
        return ToastService::getVerbosityOptions();
    }

    public function handleJoinChannel(int $chatId): void
    {
        $this->switchToChat($chatId);
        $this->exitCommandMode(); // Close the command panel

        // Show success toast
        $chatSession = ChatSession::find($chatId);
        if ($chatSession) {
            $this->showSuccessToast(
                'Channel Joined',
                "Switched to {$chatSession->channel_display}",
                'chat',
                null
            );
        }
    }

    public function filterTodos(string $searchTerm = ''): void
    {
        // Only filter if we're in command mode with todo results
        if (! $this->inCommandMode || empty($this->chatMessages)) {
            return;
        }

        $commandFragment = $this->chatMessages[0] ?? null;

        if (! $commandFragment ||
            $commandFragment['type'] !== 'command_result' ||
            ($commandFragment['data']['type'] ?? '') !== 'todo') {
            return;
        }

        // Get the original fragments from the command data
        $originalFragments = $commandFragment['data']['fragments'] ?? [];

        if (empty($searchTerm)) {
            // Show all original fragments
            $filteredFragments = $originalFragments;
        } else {
            // Filter fragments based on search term
            $filteredFragments = array_filter($originalFragments, function ($fragment) use ($searchTerm) {
                $message = $fragment['message'] ?? '';
                $title = $fragment['title'] ?? '';

                return stripos($message, $searchTerm) !== false ||
                       stripos($title, $searchTerm) !== false;
            });
        }

        // Update the command fragment with filtered results
        $this->chatMessages[0]['data']['fragments'] = array_values($filteredFragments);

        // Update the result message
        $count = count($filteredFragments);
        $status = $commandFragment['data']['status'] ?? 'open';
        $statusText = $status === 'completed' ? 'completed' : 'open';

        if (empty($searchTerm)) {
            $message = "📝 Found **{$count}** {$statusText} todo".($count !== 1 ? 's' : '');
        } else {
            $message = "📝 Found **{$count}** {$statusText} todo".($count !== 1 ? 's' : '')." matching '{$searchTerm}'";
        }

        $this->chatMessages[0]['data']['message'] = $message;
    }
}
