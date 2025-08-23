<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Actions\ParseSlashCommand;
use App\Actions\RouteFragment;
use App\Filament\Resources\FragmentResource;
use App\Models\Fragment;
use App\Services\CommandRegistry;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ChatInterface extends Page
{

    protected static string  $resource = FragmentResource::class;
    protected static ?string $slug     = 'lens';
    protected static string  $view     = 'filament.resources.fragment-resource.pages.chat-interface';

    public string  $input                 = '';
    public array   $chatMessages          = [];
    public array   $chatHistory           = [];
    public array   $commandHistory        = [];
    public ?array  $currentSession        = null;
    public         $recalledTodos         = [];
    public ?Carbon $lastActivityAt        = null;
    public int     $sessionTimeoutMinutes = 60; // ← default to 1 hour inactivity

    protected $listeners = [
        'echo:lens.chat,fragment.processed' => 'onFragmentProcessed',
    ];

    public static function shouldRegisterNavigation( array $parameters = [] ) : bool
    {
        return false;
    }

    public function getLayout() : string
    {
        return 'vendor.filament-panels.components.layout.base'; // basic Filament layout
    }

    protected static ?string $title      = null;
    protected ?string        $heading    = null;
    protected static ?string $breadcrumb = null;

    public function getTitle() : string
    {
        return '';
    }

    public function getBreadcrumb() : string
    {
        return '';
    }

    public function mount()
    {
        $this->chatMessages = Fragment::latest()
            ->take( 20 )
            ->get()
            ->reverse()
            ->map( fn( $fragment ) => [
                'type'    => $fragment->type,
                'message' => $fragment->message,
            ] )
            ->values()
            ->toArray();

        $this->recalledTodos = []; // Fragment IDs for recalled todos

    }

    public function handleInput()
    {
        $message = trim( $this->input );
        // ✅ 1. Clear Input Immediately
        $this->input = '';

        $spinnerKey = uniqid( 'spinner_', true );

        // ✅ 2. Add Temporary "Processing..." Message
        $this->chatMessages[] = [
            'key'     => $spinnerKey,
            'type'    => 'system',
            'message' => '⏳ Processing...',
        ];

        if ( str_starts_with( $message, '/' ) ) {
            $command                                  = app( ParseSlashCommand::class )( $message );
            $command->arguments[ '__currentSession' ] = $this->currentSession; // Inject current session

            try {
                $handlerClass = CommandRegistry::find( $command->command );
                $handler      = app( $handlerClass );
            } catch ( InvalidArgumentException $e ) {
                $this->removeSpinner( $spinnerKey ); // ❗ clean up spinner
                $this->chatMessages[] = [
                    'type'    => 'system',
                    'message' => "❌ Command `/{$command->command}` not recognized. Try `/help` for options.",
                ];

                return;
            }

            /** @var \App\DTOs\CommandResponse $response */
            $response = $handler->handle( $command );

            $this->removeSpinner( $spinnerKey );

            if ( ! empty( $response->shouldResetChat ) ) {
                $this->chatMessages = [];
            }

            if ( ! empty( $response->message ) ) {
                $this->chatMessages[] = [
                    'type'    => $response->type ?? 'system',
                    'message' => $response->message,
                ];
            }

            if ( ! empty( $response->fragments ) && is_array( $response->fragments ) && array_is_list( $response->fragments ) ) {
                // Handle different fragment types differently
                if ( $response->type === 'recall' ) {
                    // For recall commands, fragments are IDs - store them directly
                    $this->recalledTodos = $response->fragments;
                } else {
                    // For other commands, fragments are arrays with type/message
                    foreach ( $response->fragments as $fragment ) {
                        if ( is_array( $fragment ) && isset( $fragment['type'], $fragment['message'] ) ) {
                            $this->chatMessages[] = [
                                'type'    => $fragment[ 'type' ],
                                'message' => $fragment[ 'message' ],
                            ];
                        }
                    }
                }
            }

            if ( $response->type === 'session-start' ) {
                $this->currentSession = $response->fragments;
            }

            if ( $response->type === 'session-end' ) {
                $this->currentSession = null;
            }

            $this->commandHistory[] = $command->raw;
        } else {
            // normal fragment handling
            $fragment = app( RouteFragment::class )( $message );

            $this->removeSpinner( $spinnerKey );

            $this->chatMessages[] = [
                'type'    => $fragment->type,
                'message' => $fragment->message,
            ];
        }
    }

    protected function removeSpinner( string $spinnerKey ) : void
    {
        $this->chatMessages = array_filter( $this->chatMessages, function ( $msg ) use ( $spinnerKey ) {
            return ( $msg[ 'key' ] ?? null ) !== $spinnerKey;
        } );

        // Reindex to fix Livewire weirdness
        $this->chatMessages = array_values( $this->chatMessages );
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


    public function onFragmentProcessed( $payload )
    {
        $message = "✅ Fragment processed (Origin ID: {$payload['fragmentId']})";

        if ( ! empty( $payload[ 'children' ] ) ) {
            $message .= "\n\nFragments created:\n";
            foreach ( $payload[ 'children' ] as $fragment ) {
                $message .= "- [{$fragment['type']}] {$fragment['message']}\n";
            }
        }

        $this->chatMessages[] = [
            'type'    => 'system',
            'message' => $message,
        ];
    }

}
