<?php

namespace App\Services;

use App\Models\Documentation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class DocumentationImportService
{
    protected array $stats = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    protected array $subsystemKeywords = [
        'orchestration' => ['task', 'agent', 'sprint', 'delegation', 'assignment'],
        'ingestion' => ['obsidian', 'readwise', 'chatgpt', 'import', 'sync'],
        'pipeline' => ['fragment', 'processing', 'pipeline', 'transform'],
        'commands' => ['slash', 'command', 'yaml', 'dsl'],
        'fragments' => ['fragment', 'storage', 'retrieval', 'search'],
        'ai' => ['llm', 'embedding', 'classification', 'provider', 'openai'],
        'ui' => ['flux', 'livewire', 'react', 'component', 'interface'],
        'infrastructure' => ['database', 'queue', 'cache', 'deployment', 'horizon'],
        'testing' => ['test', 'pest', 'phpunit', 'fixture'],
    ];

    protected array $purposePatterns = [
        'guide' => ['guide', 'tutorial', 'how-to', 'walkthrough'],
        'reference' => ['api', 'reference', 'configuration', 'spec'],
        'architecture' => ['architecture', 'design', 'adr', 'diagram'],
        'troubleshooting' => ['troubleshoot', 'problem', 'issue', 'fix', 'debug'],
        'migration' => ['migration', 'upgrade', 'breaking', 'changelog'],
        'plan' => ['plan', 'sprint', 'roadmap'],
        'context' => ['context', 'background', 'history', 'decision'],
    ];

    protected array $dangerPatterns = [
        'warning', 'caution', 'important', 'danger', 'tricky',
        'confusing', 'gotcha', 'edge case', '⚠️', 'WARNING:', 'IMPORTANT:',
    ];

    protected array $solutionPatterns = [
        'solution', 'fix', 'resolution', 'workaround', 'resolved',
        'to fix', 'the solution is', 'troubleshooting', 'common issue', 'faq',
    ];

    public function importFromDirectory(string $basePath): array
    {
        $this->stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $files = File::allFiles($basePath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $this->stats['processed']++;

            try {
                $this->importFile($file->getPathname(), $basePath);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                \Log::error('Documentation import error', [
                    'file' => $file->getPathname(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->stats;
    }

    protected function importFile(string $filePath, string $basePath): void
    {
        $content = File::get($filePath);
        $relativePath = Str::after($filePath, $basePath.'/');

        [$frontmatter, $markdownContent] = $this->parseFrontmatter($content);

        $fileHash = hash('sha256', $content);
        $lastModified = File::lastModified($filePath);

        $existing = Documentation::where('file_path', $relativePath)->first();

        if ($existing && $existing->file_hash === $fileHash) {
            $this->stats['skipped']++;

            return;
        }

        $namespace = $this->extractNamespace($relativePath);
        $subsystem = $frontmatter['subsystem'] ?? $this->detectSubsystem($relativePath, $markdownContent);
        $purpose = $frontmatter['purpose'] ?? $this->detectPurpose($relativePath, $markdownContent);
        $tags = $this->mergeTags(
            $frontmatter['tags'] ?? [],
            $this->extractAutoTags($markdownContent)
        );

        $data = [
            'title' => $frontmatter['title'] ?? $this->extractTitle($markdownContent, $relativePath),
            'content' => $markdownContent,
            'excerpt' => $this->extractExcerpt($markdownContent),
            'file_path' => $relativePath,
            'namespace' => $namespace,
            'file_hash' => $fileHash,
            'subsystem' => $subsystem,
            'purpose' => $purpose,
            'tags' => $tags,
            'related_docs' => $frontmatter['related'] ?? [],
            'related_code_paths' => $frontmatter['code_paths'] ?? $this->extractCodePaths($markdownContent),
            'version' => $existing ? $existing->version + 1 : 1,
            'last_modified' => date('Y-m-d H:i:s', $lastModified),
        ];

        if ($existing) {
            $existing->update($data);
            $this->stats['updated']++;
        } else {
            Documentation::create($data);
            $this->stats['created']++;
        }
    }

    protected function parseFrontmatter(string $content): array
    {
        if (! Str::startsWith($content, '---')) {
            return [[], $content];
        }

        $parts = explode('---', $content, 3);
        if (count($parts) < 3) {
            return [[], $content];
        }

        try {
            $frontmatter = Yaml::parse($parts[1]);
            $markdown = trim($parts[2]);

            return [$frontmatter ?? [], $markdown];
        } catch (\Exception $e) {
            return [[], $content];
        }
    }

    protected function extractNamespace(string $relativePath): string
    {
        $parts = explode('/', $relativePath);
        if (count($parts) === 1) {
            return 'root';
        }

        return $parts[0];
    }

    protected function detectSubsystem(string $relativePath, string $content): ?string
    {
        $pathLower = strtolower($relativePath);
        $contentLower = strtolower($content);

        foreach ($this->subsystemKeywords as $subsystem => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($pathLower, $keyword) || Str::contains($contentLower, $keyword)) {
                    return $subsystem;
                }
            }
        }

        return null;
    }

    protected function detectPurpose(string $relativePath, string $content): ?string
    {
        $pathLower = strtolower($relativePath);
        $contentLower = strtolower($content);

        foreach ($this->purposePatterns as $purpose => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::contains($pathLower, $pattern) || Str::contains($contentLower, $pattern)) {
                    return $purpose;
                }
            }
        }

        return null;
    }

    protected function extractAutoTags(string $content): array
    {
        $tags = [];
        $contentLower = strtolower($content);

        foreach ($this->dangerPatterns as $pattern) {
            if (Str::contains($contentLower, strtolower($pattern))) {
                $tags[] = '#danger';
                break;
            }
        }

        foreach ($this->solutionPatterns as $pattern) {
            if (Str::contains($contentLower, strtolower($pattern))) {
                $tags[] = '#solution';
                break;
            }
        }

        if (preg_match('/known issue|common problem|frequently encountered/i', $content)) {
            $tags[] = '#common-issue';
        }

        return array_unique($tags);
    }

    protected function mergeTags(array $manualTags, array $autoTags): array
    {
        $normalizedManual = array_map(function ($tag) {
            return Str::startsWith($tag, '#') ? $tag : '#'.$tag;
        }, $manualTags);

        return array_unique(array_merge($normalizedManual, $autoTags));
    }

    protected function extractTitle(string $content, string $relativePath): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return basename($relativePath, '.md');
    }

    protected function extractExcerpt(string $content): ?string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && ! Str::startsWith($line, '#') && ! Str::startsWith($line, '```')) {
                return Str::limit($line, 300);
            }
        }

        return null;
    }

    protected function extractCodePaths(string $content): array
    {
        $paths = [];

        if (preg_match_all('/`(app\/[^`]+)`/i', $content, $matches)) {
            $paths = array_merge($paths, $matches[1]);
        }

        if (preg_match_all('/`(database\/[^`]+)`/i', $content, $matches)) {
            $paths = array_merge($paths, $matches[1]);
        }

        if (preg_match_all('/`(resources\/[^`]+)`/i', $content, $matches)) {
            $paths = array_merge($paths, $matches[1]);
        }

        return array_unique($paths);
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
