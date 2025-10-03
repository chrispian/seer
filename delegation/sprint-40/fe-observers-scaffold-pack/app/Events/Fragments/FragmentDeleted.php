<?php
namespace App\Events\Fragments;
class FragmentDeleted { public function __construct(public string $fragmentId, public ?string $userId = null){} }
