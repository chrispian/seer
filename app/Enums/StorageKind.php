<?php

namespace App\Enums;

enum StorageKind: string
{
    case Local = 'local';
    case Obsidian = 'obsidian';
    case Remote = 'remote';
    case S3 = 's3';
    case GDrive = 'gdrive';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local Storage',
            self::Obsidian => 'Obsidian Vault',
            self::Remote => 'Remote URL',
            self::S3 => 'Amazon S3',
            self::GDrive => 'Google Drive',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Local => 'folder',
            self::Obsidian => 'document',
            self::Remote => 'globe',
            self::S3 => 'cloud',
            self::GDrive => 'cloud',
        };
    }
}
