<?php

namespace App\Commands;

/**
 * Note List Command
 *
 * Demonstrates BaseListCommand usage.
 * Reads all configuration from fragment_type_registry DB table.
 *
 * Usage: /notes or /note
 */
class NoteListCommand extends BaseListCommand
{
    protected function getTypeSlug(): string
    {
        return 'note';
    }

    public static function getName(): string
    {
        return 'Note List';
    }

    public static function getDescription(): string
    {
        return 'List all notes using DB-driven configuration';
    }

    public static function getUsage(): string
    {
        return '/notes';
    }

    public static function getCategory(): string
    {
        return 'Content';
    }
}
