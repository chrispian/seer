<?php

use App\Services\Obsidian\WikilinkParser;

beforeEach(function () {
    $this->parser = new WikilinkParser;
});

it('parses basic wikilink', function () {
    $content = '[[Note Title]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[Note Title]]',
        'target' => 'Note Title',
        'heading' => null,
        'alias' => null,
        'position' => 0,
    ]);
});

it('parses anchor link', function () {
    $content = '[[Note Title#Heading]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[Note Title#Heading]]',
        'target' => 'Note Title',
        'heading' => 'Heading',
        'alias' => null,
        'position' => 0,
    ]);
});

it('parses alias link', function () {
    $content = '[[Note Title|Display Text]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[Note Title|Display Text]]',
        'target' => 'Note Title',
        'heading' => null,
        'alias' => 'Display Text',
        'position' => 0,
    ]);
});

it('parses combined anchor and alias link', function () {
    $content = '[[Note Title#Heading|Display Text]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[Note Title#Heading|Display Text]]',
        'target' => 'Note Title',
        'heading' => 'Heading',
        'alias' => 'Display Text',
        'position' => 0,
    ]);
});

it('parses multiple links', function () {
    $content = 'Check [[Project Plan#Goals|the goals]] and [[Meeting Notes]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(2);
    expect($result[0])->toBe([
        'raw' => '[[Project Plan#Goals|the goals]]',
        'target' => 'Project Plan',
        'heading' => 'Goals',
        'alias' => 'the goals',
        'position' => 6,
    ]);
    expect($result[1])->toBe([
        'raw' => '[[Meeting Notes]]',
        'target' => 'Meeting Notes',
        'heading' => null,
        'alias' => null,
        'position' => 43,
    ]);
});

it('ignores links in code blocks', function () {
    $content = "```\n[[Not A Link]]\n```\n[[Real Link]]";
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0]['target'])->toBe('Real Link');
});

it('ignores links in inline code', function () {
    $content = 'Use `[[Not A Link]]` but check [[Real Link]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0]['target'])->toBe('Real Link');
});

it('skips empty links', function () {
    $content = '[[]] and [[Valid Link]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0]['target'])->toBe('Valid Link');
});

it('skips whitespace-only links', function () {
    $content = '[[   ]] and [[Valid Link]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0]['target'])->toBe('Valid Link');
});

it('handles link with only heading separator', function () {
    $content = '[[Note#]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[Note#]]',
        'target' => 'Note',
        'heading' => null,
        'alias' => null,
        'position' => 0,
    ]);
});

it('handles link with only alias separator', function () {
    $content = '[[Note|]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[Note|]]',
        'target' => 'Note',
        'heading' => null,
        'alias' => null,
        'position' => 0,
    ]);
});

it('skips link with only separators', function () {
    $content = '[[#|]]';
    $result = $this->parser->parse($content);

    expect($result)->toBeEmpty();
});

it('trims whitespace from components', function () {
    $content = '[[  Note Title  #  Heading  |  Display Text  ]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe([
        'raw' => '[[  Note Title  #  Heading  |  Display Text  ]]',
        'target' => 'Note Title',
        'heading' => 'Heading',
        'alias' => 'Display Text',
        'position' => 0,
    ]);
});

it('handles multiple links on same line', function () {
    $content = 'See [[First]] and [[Second]] and [[Third]]';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(3);
    expect($result[0]['target'])->toBe('First');
    expect($result[1]['target'])->toBe('Second');
    expect($result[2]['target'])->toBe('Third');
});

it('tracks position correctly', function () {
    $content = 'Prefix [[Link One]] middle [[Link Two]] suffix';
    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(2);
    expect($result[0]['position'])->toBe(7);
    expect($result[1]['position'])->toBe(27);
});

it('handles complex markdown with multiple features', function () {
    $content = <<<'MD'
# Heading

Check the [[Project Plan#Goals|goals]] for more info.

```js
const fake = "[[Not A Link]]";
```

Also see [[Meeting Notes]] and use `[[Another Fake]]` in code.

Final link: [[Documentation#Setup]].
MD;

    $result = $this->parser->parse($content);

    expect($result)->toHaveCount(3);
    expect($result[0]['target'])->toBe('Project Plan');
    expect($result[0]['heading'])->toBe('Goals');
    expect($result[0]['alias'])->toBe('goals');
    expect($result[1]['target'])->toBe('Meeting Notes');
    expect($result[2]['target'])->toBe('Documentation');
    expect($result[2]['heading'])->toBe('Setup');
});
