<?php

final class ToolDescriptor
{
    public string $id;     // e.g., "gmail.search"

    public string $name;   // "Gmail: Search Inbox"

    public string $desc;   // one-liner

    public array $schema = []; // arg validation

    public array $scopes = []; // permission gating
}
