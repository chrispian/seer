<?php

namespace HollisLabs\ToolCrate\Console;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class DevSearchCommand extends Command
{
    protected $signature = 'tool:search {pattern} {--paths=*} {--text=} {--fixed} {--ignore} {--max=500}';
    protected $description = 'Search text/files similarly to the MCP text.search tool.';

    public function handle(): int
    {
        $pattern = (string) $this->argument('pattern');
        $fixed = (bool) $this->option('fixed');
        $ignore = (bool) $this->option('ignore');
        $max = (int) $this->option('max');

        $regex = $fixed ? '/' . preg_quote($pattern, '/') . '/' : '/' . $pattern . '/';
        if ($ignore) $regex .= 'i';
        $matches = 0;

        if ($t = $this->option('text')) {
            foreach (preg_split('/\r?\n/', (string) $t) as $i => $line) {
                if (preg_match($regex, $line)) {
                    $this->line(sprintf('%6d | %s', $i+1, $line));
                    $matches++;
                    if ($matches >= $max) return self::SUCCESS;
                }
            }
        }

        $paths = (array) $this->option('paths');
        if ($paths) {
            $finder = new Finder();
            $finder->in($paths)->ignoreDotFiles(true)->files();
            foreach ($finder as $file) {
                $contents = @file_get_contents($file->getRealPath());
                if ($contents === false) continue;
                $i = 0;
                foreach (preg_split('/\r?\n/', $contents) as $line) {
                    $i++;
                    if (preg_match($regex, $line)) {
                        $this->line($file->getRealPath() . ':' . $i . ' | ' . $line);
                        $matches++;
                        if ($matches >= $max) return self::SUCCESS;
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
