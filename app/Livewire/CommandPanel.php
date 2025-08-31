<?php

namespace App\Livewire;

use Livewire\Component;

class CommandPanel extends Component
{
    public string $type = '';

    public array $data = [];

    public bool $isVisible = false;

    public function mount(string $type = '', array $data = []): void
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function show(string $type, array $data = []): void
    {
        $this->type = $type;
        $this->data = $data;
        $this->isVisible = true;
        $this->dispatch('panel-opened');
    }

    public function hide(): void
    {
        $this->isVisible = false;
        $this->dispatch('panel-closed');
    }

    public function confirmClear(): void
    {
        // Dispatch event to parent component to actually clear the chat
        $this->dispatch('clear-chat-confirmed');
        $this->hide();
    }

    public function render()
    {
        return view('livewire.command-panel');
    }
}
