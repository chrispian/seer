<?php

namespace App\Services\Obsidian;

class WikilinkParser
{
    public function parse(string $content): array
    {
        $content = $this->removeCodeBlocks($content);
        $content = $this->removeInlineCode($content);

        $links = [];
        $pattern = '/\[\[([^\]]+)\]\]/';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $raw = $match[0];
                $position = $match[1];
                $innerContent = $matches[1][$index][0];

                $parsed = $this->parseLink($raw, $innerContent, $position);

                if ($parsed !== null) {
                    $links[] = $parsed;
                }
            }
        }

        return $links;
    }

    private function parseLink(string $raw, string $innerContent, int $position): ?array
    {
        $innerContent = trim($innerContent);

        if (empty($innerContent)) {
            return null;
        }

        $alias = null;
        $target = $innerContent;

        if (str_contains($innerContent, '|')) {
            [$target, $alias] = array_map('trim', explode('|', $innerContent, 2));
            if (empty($alias)) {
                $alias = null;
            }
        }

        $heading = null;
        if (str_contains($target, '#')) {
            [$target, $heading] = array_map('trim', explode('#', $target, 2));
            if (empty($heading)) {
                $heading = null;
            }
        }

        $target = trim($target);
        if (empty($target)) {
            return null;
        }

        return [
            'raw' => $raw,
            'target' => $target,
            'heading' => $heading,
            'alias' => $alias,
            'position' => $position,
        ];
    }

    private function removeCodeBlocks(string $content): string
    {
        return preg_replace('/```[\s\S]*?```/', '', $content);
    }

    private function removeInlineCode(string $content): string
    {
        return preg_replace('/`[^`]+`/', '', $content);
    }
}
