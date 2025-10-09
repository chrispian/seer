<?php

namespace App\Services\Obsidian;

use App\DTOs\EnrichedObsidianFragment;
use App\DTOs\ParsedObsidianNote;
use Illuminate\Support\Str;

class ObsidianFragmentPipeline
{
    private const PATH_TYPE_MAP = [
        'contacts' => 'contact',
        'people' => 'contact',
        'meetings' => 'meeting',
        'meeting notes' => 'meeting',
        'tasks' => 'task',
        'todo' => 'task',
        'to do' => 'task',
        'projects' => 'project',
        'ideas' => 'idea',
        'references' => 'reference',
        'clippings' => 'clip',
        'bookmarks' => 'bookmark',
        'daily notes' => 'log',
        'journal' => 'log',
    ];

    private const CONTENT_PATTERNS = [
        '/^#+\s+Meeting:/im' => 'meeting',
        '/^##\s+Action Items/im' => 'meeting',
        '/^Project:/im' => 'project',
    ];

    private const CUSTOM_METADATA_FIELDS = [
        'author',
        'date',
        'project',
        'priority',
        'status',
        'category',
        'url',
        'source_url',
    ];

    public function process(
        ParsedObsidianNote $parsed,
        string $relativePath,
        ?string $folderName
    ): EnrichedObsidianFragment {
        $type = $this->inferType($parsed, $relativePath);
        $tags = $this->generateTags($parsed, $relativePath);
        $customMetadata = $this->extractCustomMetadata($parsed->frontMatter);

        return new EnrichedObsidianFragment(
            type: $type,
            tags: $tags,
            customMetadata: $customMetadata,
        );
    }

    private function inferType(ParsedObsidianNote $parsed, string $relativePath): string
    {
        if (isset($parsed->frontMatter['type']) && is_string($parsed->frontMatter['type'])) {
            return Str::lower(trim($parsed->frontMatter['type']));
        }

        $pathType = $this->inferTypeFromPath($relativePath);
        if ($pathType !== null) {
            return $pathType;
        }

        $contentType = $this->inferTypeFromContent($parsed->body);
        if ($contentType !== null) {
            return $contentType;
        }

        return 'note';
    }

    private function inferTypeFromPath(string $relativePath): ?string
    {
        $pathLower = Str::lower($relativePath);

        foreach (self::PATH_TYPE_MAP as $pathSegment => $type) {
            if (str_contains($pathLower, Str::lower($pathSegment).'/')) {
                return $type;
            }
        }

        return null;
    }

    private function inferTypeFromContent(string $content): ?string
    {
        if (preg_match('/^-\s+\[[x ]\]/m', $content)) {
            return 'task';
        }

        foreach (self::CONTENT_PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $content)) {
                return $type;
            }
        }

        return null;
    }

    private function generateTags(ParsedObsidianNote $parsed, string $relativePath): array
    {
        $tags = [];

        $tags = array_merge($tags, $this->extractFrontMatterTags($parsed->frontMatter));

        $tags = array_merge($tags, $this->extractPathTags($relativePath));

        $tags = array_merge($tags, $this->extractContentHashtags($parsed->body));

        $tags[] = 'obsidian';

        return $this->normalizeTags($tags);
    }

    private function extractFrontMatterTags(array $frontMatter): array
    {
        $tags = [];

        if (isset($frontMatter['tags'])) {
            if (is_array($frontMatter['tags'])) {
                $tags = $frontMatter['tags'];
            } elseif (is_string($frontMatter['tags'])) {
                $tags = array_map('trim', explode(',', $frontMatter['tags']));
            }
        }

        return array_filter($tags, fn ($tag) => is_string($tag) && $tag !== '');
    }

    private function extractPathTags(string $relativePath): array
    {
        $pathParts = explode('/', trim($relativePath, '/'));

        array_pop($pathParts);

        return array_filter($pathParts, fn ($part) => $part !== '');
    }

    private function extractContentHashtags(string $content): array
    {
        preg_match_all('/#([a-zA-Z0-9_-]+)/', $content, $matches);

        return $matches[1] ?? [];
    }

    private function normalizeTags(array $tags): array
    {
        $normalized = array_map(function ($tag) {
            $tag = Str::lower(trim($tag));
            $tag = str_replace('#', '', $tag);
            $tag = Str::slug($tag, '_');

            return $tag;
        }, $tags);

        $normalized = array_filter($normalized, fn ($tag) => $tag !== '');

        return array_values(array_unique($normalized));
    }

    private function extractCustomMetadata(array $frontMatter): array
    {
        $metadata = [];

        foreach (self::CUSTOM_METADATA_FIELDS as $field) {
            if (isset($frontMatter[$field])) {
                $metadata[$field] = $frontMatter[$field];
            }
        }

        return $metadata;
    }
}
