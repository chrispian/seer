<?php

namespace App\Services\Obsidian;

use App\DTOs\ParsedObsidianNote;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ObsidianMarkdownParser
{
    public function __construct(
        private readonly WikilinkParser $wikilinkParser,
    ) {}

    public function parse(string $content, ?string $filename = null): ParsedObsidianNote
    {
        $frontMatter = [];
        $body = $content;

        if (preg_match('/^---\n(.*?)\n---\n(.*)$/s', $content, $matches)) {
            try {
                $frontMatter = Yaml::parse($matches[1]) ?? [];
                if (! is_array($frontMatter)) {
                    $frontMatter = [];
                }
            } catch (ParseException $e) {
                Log::warning('Failed to parse YAML front matter', [
                    'error' => $e->getMessage(),
                ]);
                $frontMatter = [];
            }

            $body = $matches[2];
        }

        $bodyWithoutLinks = $this->stripWikilinks($body);

        $title = $this->extractTitle($bodyWithoutLinks, $frontMatter, $filename);

        $tags = $this->extractTags($frontMatter);

        $links = $this->wikilinkParser->parse($body);

        return new ParsedObsidianNote(
            title: $title,
            body: trim($body),
            frontMatter: $frontMatter,
            tags: $tags,
            links: $links,
        );
    }

    private function stripWikilinks(string $content): string
    {
        return preg_replace('/\[\[([^\]]+)\]\]/', '$1', $content);
    }

    private function extractTitle(string $body, array $frontMatter, ?string $filename = null): string
    {
        if (! empty($frontMatter['title'])) {
            return (string) $frontMatter['title'];
        }

        if ($filename) {
            $titleFromFilename = pathinfo($filename, PATHINFO_FILENAME);
            if ($titleFromFilename !== '') {
                return $titleFromFilename;
            }
        }

        if (preg_match('/^#\s+(.+)$/m', $body, $matches)) {
            return trim($matches[1]);
        }

        $lines = explode("\n", $body);
        $firstLine = trim($lines[0] ?? '');

        if ($firstLine !== '' && strlen($firstLine) <= 100) {
            return $firstLine;
        }

        return 'Untitled Note';
    }

    private function extractTags(array $frontMatter): array
    {
        $tags = [];

        if (isset($frontMatter['tags'])) {
            if (is_array($frontMatter['tags'])) {
                $tags = $frontMatter['tags'];
            } elseif (is_string($frontMatter['tags'])) {
                $tags = array_map('trim', explode(',', $frontMatter['tags']));
            }
        }

        return array_values(array_filter($tags, fn ($tag) => is_string($tag) && $tag !== ''));
    }
}
