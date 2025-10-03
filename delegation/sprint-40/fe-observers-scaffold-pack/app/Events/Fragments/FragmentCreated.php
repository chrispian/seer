<?php
namespace App\Events\Fragments;
class FragmentCreated { public function __construct(public string $fragmentId, public string $type, public ?string $userId = null){} }
