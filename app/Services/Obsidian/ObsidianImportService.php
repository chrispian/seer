<?php

namespace App\Services\Obsidian;

use App\Actions\InferFragmentType;
use App\Models\Fragment;
use App\Models\FragmentLink;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ObsidianImportService
{
    public function __construct(
        private readonly ObsidianMarkdownParser $parser,
        private readonly DatabaseManager $db,
        private readonly ObsidianFragmentPipeline $pipeline,
        private readonly LinkResolver $linkResolver,
    ) {}

    public function import(?string $vaultPath = null, bool $dryRun = false, ?bool $enrich = null, bool $force = false): array
    {
        $user = User::query()->firstOrFail();
        $settings = $user->profile_settings ?? [];
        $obsidianSettings = $settings['integrations']['obsidian'] ?? [];

        $path = $vaultPath ?? $obsidianSettings['vault_path'] ?? null;
        if (empty($path)) {
            throw new \RuntimeException('Obsidian vault path is not configured.');
        }

        if (! File::isDirectory($path)) {
            throw new \RuntimeException("Obsidian vault path does not exist or is not a directory: {$path}");
        }

        if ($enrich === null) {
            $enrich = $obsidianSettings['enrich_enabled'] ?? false;
        }

        Source::query()->firstOrCreate(
            ['key' => 'obsidian'],
            ['label' => 'Obsidian', 'meta' => []]
        );

        $codexVault = Vault::firstOrCreate(
            ['name' => 'codex'],
            [
                'description' => 'Imported notes and knowledge base',
                'is_default' => false,
                'sort_order' => 99,
            ]
        );

        $rootProject = Project::forVault($codexVault->id)
            ->where('name', 'Root')
            ->first();

        if (! $rootProject) {
            $rootProject = Project::create([
                'vault_id' => $codexVault->id,
                'name' => 'Root',
                'description' => 'Root project for imported notes',
                'is_default' => true,
                'sort_order' => 0,
            ]);
        }

        $stats = [
            'files_total' => 0,
            'files_imported' => 0,
            'files_updated' => 0,
            'files_skipped' => 0,
            'types_inferred' => [],
            'dry_run' => $dryRun,
            'enrich' => $enrich,
            'force' => $force,
        ];

        $markdownFiles = $this->findMarkdownFiles($path);
        $stats['files_total'] = count($markdownFiles);

        foreach ($markdownFiles as $filePath) {
            try {
                $relativePath = Str::after($filePath, $path.'/');
                $folderName = $this->extractFolderName($relativePath);
                $fileModifiedAt = CarbonImmutable::createFromTimestamp(filemtime($filePath));

                $existingFragment = Fragment::query()
                    ->where('metadata->obsidian_path', $relativePath)
                    ->first();

                if ($existingFragment && ! $force) {
                    $storedModifiedAt = isset($existingFragment->metadata['obsidian_modified_at'])
                        ? CarbonImmutable::parse($existingFragment->metadata['obsidian_modified_at'])
                        : null;

                    if ($storedModifiedAt && $fileModifiedAt->lte($storedModifiedAt)) {
                        $stats['files_skipped']++;

                        continue;
                    }
                }

                $content = File::get($filePath);
                $filename = basename($filePath);
                $parsed = $this->parser->parse($content, $filename);

                if ($dryRun) {
                    if ($existingFragment) {
                        $stats['files_updated']++;
                    } else {
                        $stats['files_imported']++;
                    }

                    continue;
                }

                $this->db->transaction(function () use ($parsed, $relativePath, $folderName, $fileModifiedAt, $codexVault, $rootProject, $existingFragment, $enrich, &$stats) {
                    $fragment = $existingFragment ?? new Fragment;

                    $enriched = $this->pipeline->process($parsed, $relativePath, $folderName);

                    $tags = array_unique(array_merge($enriched->tags, $parsed->tags));
                    $tags = array_values(array_map(fn ($tag) => Str::slug($tag), $tags));

                    $fragment->fill([
                        'message' => $parsed->body,
                        'title' => Str::limit($parsed->title, 255),
                        'type' => $enriched->type,
                        'source' => 'Obsidian',
                        'source_key' => 'obsidian',
                        'vault' => $codexVault->name,
                        'project_id' => $rootProject->id,
                    ]);

                    $fragment->tags = $tags;

                    $fragment->metadata = array_merge($fragment->metadata ?? [], [
                        'obsidian_path' => $relativePath,
                        'obsidian_modified_at' => $fileModifiedAt->toIso8601String(),
                        'front_matter' => $parsed->frontMatter,
                        'custom_fields' => $enriched->customMetadata,
                        'obsidian_links' => $parsed->links,
                    ]);

                    $fragment->save();

                    if (! isset($stats['types_inferred'][$enriched->type])) {
                        $stats['types_inferred'][$enriched->type] = 0;
                    }
                    $stats['types_inferred'][$enriched->type]++;

                    if ($enrich && ! $existingFragment) {
                        $this->enrichFragment($fragment);
                    }

                    if ($existingFragment) {
                        $stats['files_updated']++;
                    } else {
                        $stats['files_imported']++;
                    }
                });
            } catch (\Throwable $e) {
                Log::error('Failed to import Obsidian note', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ]);
                $stats['files_skipped']++;
            }
        }

        if (! $dryRun) {
            $this->resolveLinks($stats);

            $settings['integrations'] = $settings['integrations'] ?? [];
            $settings['integrations']['obsidian'] = array_merge(
                $settings['integrations']['obsidian'] ?? [],
                [
                    'last_synced_at' => now()->toIso8601String(),
                    'file_count' => $stats['files_total'],
                    'last_import_stats' => $stats,
                ]
            );

            $user->update(['profile_settings' => $settings]);
        }

        return $stats;
    }

    private function findMarkdownFiles(string $path): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function extractFolderName(string $relativePath): ?string
    {
        $parts = explode('/', $relativePath);

        if (count($parts) > 1) {
            return $parts[0];
        }

        return null;
    }

    private function enrichFragment(Fragment $fragment): void
    {
        try {
            $inferType = app(InferFragmentType::class);
            $inferredType = $inferType($fragment);

            if ($inferredType && $inferredType !== 'note') {
                $fragment->type = $inferredType;
                $fragment->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to enrich Obsidian fragment', [
                'fragment_id' => $fragment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveLinks(array &$stats): void
    {
        $linksResolved = 0;
        $linksOrphaned = 0;

        $fragments = Fragment::where('source_key', 'obsidian')
            ->whereNotNull('metadata->obsidian_links')
            ->get();

        foreach ($fragments as $fragment) {
            $links = $fragment->metadata['obsidian_links'] ?? [];
            if (empty($links)) {
                continue;
            }

            $result = $this->linkResolver->resolve($links, $fragment->id);

            foreach ($result['resolved'] as $link) {
                FragmentLink::updateOrCreate(
                    [
                        'from_id' => $fragment->id,
                        'to_id' => $link['target_fragment_id'],
                        'relation' => 'references',
                    ]
                );
            }

            $linksResolved += count($result['resolved']);
            $linksOrphaned += count($result['orphans']);
        }

        $stats['links_resolved'] = $linksResolved;
        $stats['links_orphaned'] = $linksOrphaned;
    }
}
