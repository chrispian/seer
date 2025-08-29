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
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Livewire\Attributes\On;

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

    // Recall palette state
    public bool $showRecallPalette = false;
    
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

    protected $listeners = [
        'echo:lens.chat,fragment.processed' => 'onFragmentProcessed',
        'undo-fragment' => 'handleUndoDeleteObject',
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

            try {
                $handlerClass = CommandRegistry::find($command->command);
                $handler = app($handlerClass);
            } catch (InvalidArgumentException $e) {
                $this->removeSpinner($spinnerKey); // ❗ clean up spinner
                $this->chatMessages[] = [
                    'type' => 'system',
                    'type_id' => $this->getSystemTypeId(),
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
                'type' => $fragment->type?->value ?? 'log',
                'type_id' => $fragment->type_id,
                'message' => $fragment->message,
                'created_at' => $fragment->created_at,
            ];
        }

        // Save the updated chat session after any input
        $this->saveCurrentChatSession();
    }

    protected function getSystemTypeId(): int
    {
        return Type::where('value', 'system')->first()?->id ?? Type::where('value', 'log')->first()->id;
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
            'type_id' => $this->getSystemTypeId(),
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
            $undoId = 'chat-' . $chatSessionId;
            
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

        $fragment = Fragment::find($fragmentId);

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
            'type' => $fragment->type->value,
        ]);

        // Store message before deletion for toast
        $fragmentMessage = $fragment->message;

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
        $restoredFragment = app(\App\Actions\UndoFragmentDelete::class)($fragmentId);

        if ($restoredFragment) {
            // Add back to chat messages
            $this->chatMessages[] = [
                'id' => $restoredFragment->id,
                'type' => $restoredFragment->type,
                'message' => $restoredFragment->message,
                'created_at' => $restoredFragment->created_at,
            ];

            // Save the updated chat session
            $this->saveCurrentChatSession();

            // Dispatch event to refresh bookmark widget
            $this->js("
                window.dispatchEvent(new CustomEvent('fragment-restored', {
                    detail: { fragmentId: {$restoredFragment->id} }
                }));
            ");

            // Show success toast instead of chat message
            $this->dispatch('show-success-toast', [
                'message' => 'Fragment restored successfully',
                'objectType' => 'fragment'
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
                'objectType' => 'fragment'
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
                'object_type' => $objectType
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
                        'session_title' => $session->title
                    ]);
                    
                    // Show success toast for chat restoration
                    $this->dispatch('show-success-toast', [
                        'message' => "Chat \"{$session->title}\" restored successfully",
                        'objectType' => 'chat'
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
        if ($logDismissal && !empty($this->recallQuery) && !empty($this->recallResults)) {
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
        $this->recallResults = [];
        $this->recallSuggestions = [];
        $this->recallAutocomplete = [];
        $this->selectedRecallIndex = 0;
        $this->recallLoading = false;
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
            // Parse the search query
            $grammarParser = app(ParseSearchGrammar::class);
            $parsedGrammar = $grammarParser($this->recallQuery);
            
            // Perform the search
            $searchAction = app(SearchFragments::class);
            
            // Get the vault name for searching
            $vaultName = null;
            if ($this->currentVaultId) {
                $vault = \App\Models\Vault::find($this->currentVaultId);
                $vaultName = $vault?->name;
            }
            
            $results = $searchAction(
                query: $this->recallQuery,
                vault: $vaultName,
                projectId: $this->currentProjectId,
                sessionId: $this->currentChatSessionId ? "session-{$this->currentChatSessionId}" : null,
                limit: 10
            );
            
            // Format results for display
            $this->recallResults = $results->map(function ($fragment) {
                return [
                    'id' => $fragment->id,
                    'type' => $fragment->type instanceof \BackedEnum ? $fragment->type->value : $fragment->type,
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
            Log::error('Recall search failed', [
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
        if (!isset($this->recallResults[$index])) {
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
            $this->selectedRecallIndex = $this->selectedRecallIndex > 0 
                ? $this->selectedRecallIndex - 1 
                : $maxIndex;
        } elseif ($direction === 'down') {
            $this->selectedRecallIndex = $this->selectedRecallIndex < $maxIndex 
                ? $this->selectedRecallIndex + 1 
                : 0;
        }
    }
    
    public function applySuggestion(array $suggestion): void
    {
        if ($suggestion['type'] === 'filter') {
            // Append filter to query
            $this->recallQuery = trim($this->recallQuery . ' ' . $suggestion['text']);
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
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }
}
