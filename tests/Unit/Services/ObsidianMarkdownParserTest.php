<?php

use App\Services\Obsidian\ObsidianMarkdownParser;

test('parses markdown with valid YAML front matter', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
---
title: My Note
tags: [tag1, tag2]
---
# Heading
Content here
MD;

    $result = $parser->parse($content);

    expect($result->title)->toBe('My Note')
        ->and($result->frontMatter)->toHaveKey('title')
        ->and($result->frontMatter)->toHaveKey('tags')
        ->and($result->tags)->toBe(['tag1', 'tag2'])
        ->and($result->body)->toContain('# Heading');
});

test('extracts title from filename when no front matter title', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
# My Heading
Content here
MD;

    $result = $parser->parse($content, 'My File.md');

    expect($result->title)->toBe('My File');
});

test('extracts title from H1 when no front matter title or filename', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
# My Heading
Content here
MD;

    $result = $parser->parse($content);

    expect($result->title)->toBe('My Heading');
});

test('handles markdown without front matter', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
# Simple Note
Just some content
MD;

    $result = $parser->parse($content);

    expect($result->frontMatter)->toBe([])
        ->and($result->title)->toBe('Simple Note')
        ->and($result->body)->toContain('# Simple Note');
});

test('handles malformed YAML gracefully', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
---
title: Valid
bad yaml: [unclosed
---
Content
MD;

    $result = $parser->parse($content);

    expect($result->frontMatter)->toBe([])
        ->and($result->body)->toContain('Content');
});

test('strips wikilinks from body', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
# Note
Link to [[another note]] here
And [[yet another]]
MD;

    $result = $parser->parse($content);

    expect($result->title)->not->toContain('[[')
        ->and($result->title)->not->toContain(']]');
});

test('extracts tags from front matter as array', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
---
tags:
  - work
  - project
---
Content
MD;

    $result = $parser->parse($content);

    expect($result->tags)->toBe(['work', 'project']);
});

test('extracts tags from front matter as comma-separated string', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
---
tags: work, project, idea
---
Content
MD;

    $result = $parser->parse($content);

    expect($result->tags)->toBe(['work', 'project', 'idea']);
});

test('handles empty files', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $result = $parser->parse('');

    expect($result->title)->toBe('Untitled Note')
        ->and($result->body)->toBe('')
        ->and($result->frontMatter)->toBe([])
        ->and($result->tags)->toBe([]);
});

test('prefers front matter title over filename', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = <<<'MD'
---
title: Front Matter Title
---
Content
MD;

    $result = $parser->parse($content, 'filename.md');

    expect($result->title)->toBe('Front Matter Title');
});

test('uses first line as title when no H1, front matter title, or filename', function () {
    $parser = app(ObsidianMarkdownParser::class);

    $content = "This is the first line\nAnd more content";

    $result = $parser->parse($content);

    expect($result->title)->toBe('This is the first line');
});
