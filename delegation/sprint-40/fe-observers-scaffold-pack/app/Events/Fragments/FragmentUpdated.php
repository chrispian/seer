<?php

namespace App\Events\Fragments;

class FragmentUpdated
{
    public function __construct(public string $fragmentId, public array $diff, public ?string $userId = null) {}
}
