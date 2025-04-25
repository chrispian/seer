<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use App\Models\Fragment;
use Filament\Resources\Pages\Page;
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
        $fragment = Fragment::create([
            'vault' => 'default',
            'type' => 'log',
            'message' => $this->input,
            'source' => 'chat',
        ]);

        $this->chatHistory[] = $fragment->toArray();
        $this->input = '';
    }
}
