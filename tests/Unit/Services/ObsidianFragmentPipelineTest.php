<?php

use App\DTOs\EnrichedObsidianFragment;
use App\DTOs\ParsedObsidianNote;
use App\Services\Obsidian\ObsidianFragmentPipeline;

beforeEach(function () {
    $this->pipeline = new ObsidianFragmentPipeline();
});

describe('Type Inference - Front Matter Override', function () {
    it('infers type from front matter type field', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: '# Test Note',
            frontMatter: ['type' => 'meeting'],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Projects/Test.md', 'Projects');

        expect($result->type)->toBe('meeting');
    });

    it('normalizes front matter type to lowercase', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: '# Test Note',
            frontMatter: ['type' => 'MEETING'],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Projects/Test.md', 'Projects');

        expect($result->type)->toBe('meeting');
    });

    it('trims whitespace from front matter type', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: '# Test Note',
            frontMatter: ['type' => '  task  '],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Projects/Test.md', 'Projects');

        expect($result->type)->toBe('task');
    });

    it('front matter type overrides path-based inference', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: '# Test Note',
            frontMatter: ['type' => 'meeting'],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Contacts/Person.md', 'Contacts');

        expect($result->type)->toBe('meeting');
    });
});

describe('Type Inference - Path-based', function () {
    it('infers contact type from Contacts folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'John Doe',
            body: '# John Doe',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Contacts/John Doe.md', 'Contacts');

        expect($result->type)->toBe('contact');
    });

    it('infers contact type from People folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Jane Doe',
            body: '# Jane Doe',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'People/Jane Doe.md', 'People');

        expect($result->type)->toBe('contact');
    });

    it('infers meeting type from Meetings folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Standup',
            body: '# Standup',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Meetings/Standup.md', 'Meetings');

        expect($result->type)->toBe('meeting');
    });

    it('infers meeting type from Meeting Notes folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Q1 Planning',
            body: '# Q1 Planning',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Meeting Notes/Q1.md', 'Meeting Notes');

        expect($result->type)->toBe('meeting');
    });

    it('infers task type from Tasks folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Todo',
            body: '# Todo',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Tasks/Todo.md', 'Tasks');

        expect($result->type)->toBe('task');
    });

    it('infers task type from TODO folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Backlog',
            body: '# Backlog',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'TODO/Backlog.md', 'TODO');

        expect($result->type)->toBe('task');
    });

    it('infers project type from Projects folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'App',
            body: '# App',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Projects/App.md', 'Projects');

        expect($result->type)->toBe('project');
    });

    it('infers idea type from Ideas folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Feature',
            body: '# Feature',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Ideas/Feature.md', 'Ideas');

        expect($result->type)->toBe('idea');
    });

    it('infers reference type from References folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Docs',
            body: '# Docs',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'References/Docs.md', 'References');

        expect($result->type)->toBe('reference');
    });

    it('infers clip type from Clippings folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Article',
            body: '# Article',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Clippings/Article.md', 'Clippings');

        expect($result->type)->toBe('clip');
    });

    it('infers bookmark type from Bookmarks folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Links',
            body: '# Links',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Bookmarks/Links.md', 'Bookmarks');

        expect($result->type)->toBe('bookmark');
    });

    it('infers log type from Daily Notes folder', function () {
        $parsed = new ParsedObsidianNote(
            title: '2025-01-06',
            body: '# 2025-01-06',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Daily Notes/2025-01-06.md', 'Daily Notes');

        expect($result->type)->toBe('log');
    });

    it('infers log type from Journal folder', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Entry',
            body: '# Entry',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Journal/Entry.md', 'Journal');

        expect($result->type)->toBe('log');
    });

    it('is case insensitive for path matching', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result1 = $this->pipeline->process($parsed, 'contacts/test.md', 'contacts');
        $result2 = $this->pipeline->process($parsed, 'CONTACTS/test.md', 'CONTACTS');
        $result3 = $this->pipeline->process($parsed, 'Contacts/test.md', 'Contacts');

        expect($result1->type)->toBe('contact');
        expect($result2->type)->toBe('contact');
        expect($result3->type)->toBe('contact');
    });

    it('matches nested paths correctly', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Work/Projects/Q1/file.md', 'Work/Projects/Q1');

        expect($result->type)->toBe('project');
    });
});

describe('Type Inference - Content Pattern Matching', function () {
    it('infers meeting type from Meeting: heading', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: "# Meeting: Weekly Standup\n\nDiscussion points...",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Notes/test.md', 'Notes');

        expect($result->type)->toBe('meeting');
    });

    it('infers meeting type from Action Items heading', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: "# Notes\n\n## Action Items\n\n- Follow up on task",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Notes/test.md', 'Notes');

        expect($result->type)->toBe('meeting');
    });

    it('infers project type from Project: prefix', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: "Project: New Feature Development\n\nDetails...",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Notes/test.md', 'Notes');

        expect($result->type)->toBe('project');
    });

    it('infers task type from checkbox', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: "# Tasks\n\n- [ ] Complete feature\n- [x] Write tests",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Notes/test.md', 'Notes');

        expect($result->type)->toBe('task');
    });

    it('infers task type from checked checkbox', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test Note',
            body: "- [x] Completed task",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Notes/test.md', 'Notes');

        expect($result->type)->toBe('task');
    });
});

describe('Type Inference - Default Fallback', function () {
    it('defaults to note when no patterns match', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Random Note',
            body: '# Random Note\n\nSome content here.',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Random/File.md', 'Random');

        expect($result->type)->toBe('note');
    });

    it('defaults to note for root level files', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Root Note',
            body: '# Root Note',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'RootFile.md', null);

        expect($result->type)->toBe('note');
    });

    it('defaults to note with empty content', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Empty',
            body: '',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Empty.md', null);

        expect($result->type)->toBe('note');
    });
});

describe('Tag Generation - Front Matter Tags', function () {
    it('extracts tags from front matter array', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['work', 'urgent']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('urgent');
    });

    it('extracts tags from front matter comma-separated string', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => 'work, urgent, important'],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('urgent');
        expect($result->tags)->toContain('important');
    });

    it('trims whitespace from comma-separated tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => '  work  ,  urgent  '],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('urgent');
    });

    it('filters out empty tags from front matter', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['work', '', 'urgent', null]],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('urgent');
        expect($result->tags)->not->toContain('');
    });
});

describe('Tag Generation - Folder-based Tags', function () {
    it('extracts folder names as tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Work/Projects/Q1/file.md', 'Work/Projects/Q1');

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('projects');
        expect($result->tags)->toContain('q1');
    });

    it('excludes filename from folder tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Work/file.md', 'Work');

        expect($result->tags)->toContain('work');
        expect($result->tags)->not->toContain('file');
        expect($result->tags)->not->toContain('md');
    });

    it('handles root level files with no folder tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'file.md', null);

        expect($result->tags)->toBe(['obsidian']);
    });
});

describe('Tag Generation - Content Hashtags', function () {
    it('extracts hashtags from content', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test\n\nThis is #meeting and #urgent content.',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('meeting');
        expect($result->tags)->toContain('urgent');
    });

    it('extracts hashtags with underscores and hyphens', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test\n\n#work_task #follow-up',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work_task');
        expect($result->tags)->toContain('follow_up');
    });

    it('extracts multiple hashtags from multiline content', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: "# Test\n\n#tag1\n\nSome content\n\n#tag2 more text #tag3",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('tag1');
        expect($result->tags)->toContain('tag2');
        expect($result->tags)->toContain('tag3');
    });
});

describe('Tag Generation - Obsidian Tag', function () {
    it('always includes obsidian tag', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('obsidian');
    });

    it('includes obsidian tag even with other tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test #custom',
            frontMatter: ['tags' => ['work']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Projects/test.md', 'Projects');

        expect($result->tags)->toContain('obsidian');
        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('custom');
        expect($result->tags)->toContain('projects');
    });
});

describe('Tag Generation - Merging and Deduplication', function () {
    it('merges tags from all sources', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test #content-tag',
            frontMatter: ['tags' => ['frontmatter-tag']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Work/test.md', 'Work');

        expect($result->tags)->toContain('frontmatter_tag');
        expect($result->tags)->toContain('content_tag');
        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('obsidian');
    });

    it('deduplicates tags from multiple sources', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test #work',
            frontMatter: ['tags' => ['work', 'urgent']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Work/test.md', 'Work');

        $workCount = count(array_filter($result->tags, fn ($tag) => $tag === 'work'));
        expect($workCount)->toBe(1);
    });

    it('normalizes and deduplicates similar tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test #Work-Task',
            frontMatter: ['tags' => ['Work Task', 'work_task']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        $workTaskCount = count(array_filter($result->tags, fn ($tag) => $tag === 'work_task'));
        expect($workTaskCount)->toBe(1);
    });
});

describe('Tag Normalization', function () {
    it('normalizes tags to lowercase', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['WORK', 'Urgent', 'ImPoRtAnT']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('urgent');
        expect($result->tags)->toContain('important');
        expect($result->tags)->not->toContain('WORK');
        expect($result->tags)->not->toContain('Urgent');
    });

    it('converts spaces to underscores in tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['work task', 'high priority']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work_task');
        expect($result->tags)->toContain('high_priority');
    });

    it('converts hyphens to underscores in tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['work-task', 'follow-up']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work_task');
        expect($result->tags)->toContain('follow_up');
    });

    it('removes hash symbols from tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['#work', '#urgent']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('urgent');
    });

    it('filters out empty normalized tags', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['tags' => ['', '   ', '#', 'valid']],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toContain('valid');
        expect($result->tags)->not->toContain('');
    });
});

describe('Custom Metadata Extraction', function () {
    it('extracts all standard custom metadata fields', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [
                'author' => 'John Doe',
                'date' => '2025-01-06',
                'project' => 'Seer',
                'priority' => 'high',
                'status' => 'active',
                'category' => 'development',
                'url' => 'https://example.com',
                'source_url' => 'https://source.com',
            ],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->customMetadata)->toBe([
            'author' => 'John Doe',
            'date' => '2025-01-06',
            'project' => 'Seer',
            'priority' => 'high',
            'status' => 'active',
            'category' => 'development',
            'url' => 'https://example.com',
            'source_url' => 'https://source.com',
        ]);
    });

    it('extracts only present custom metadata fields', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [
                'author' => 'John Doe',
                'priority' => 'high',
            ],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->customMetadata)->toBe([
            'author' => 'John Doe',
            'priority' => 'high',
        ]);
    });

    it('returns empty array when no custom metadata present', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->customMetadata)->toBe([]);
    });

    it('ignores non-standard front matter fields', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [
                'author' => 'John Doe',
                'custom_field' => 'value',
                'another_field' => 'another value',
            ],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->customMetadata)->toBe([
            'author' => 'John Doe',
        ]);
        expect($result->customMetadata)->not->toHaveKey('custom_field');
        expect($result->customMetadata)->not->toHaveKey('another_field');
    });
});

describe('Edge Cases', function () {
    it('handles empty front matter gracefully', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result)->toBeInstanceOf(EnrichedObsidianFragment::class);
        expect($result->type)->toBe('note');
        expect($result->tags)->toBe(['obsidian']);
        expect($result->customMetadata)->toBe([]);
    });

    it('handles empty content gracefully', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Empty',
            body: '',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'empty.md', null);

        expect($result->type)->toBe('note');
        expect($result->tags)->toBe(['obsidian']);
    });

    it('handles whitespace-only content gracefully', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Whitespace',
            body: "   \n\n   \n   ",
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'whitespace.md', null);

        expect($result->type)->toBe('note');
        expect($result->tags)->toBe(['obsidian']);
    });

    it('handles null folder name gracefully', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Root',
            body: '# Root',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'root.md', null);

        expect($result)->toBeInstanceOf(EnrichedObsidianFragment::class);
        expect($result->tags)->toBe(['obsidian']);
    });

    it('handles complex nested paths', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process(
            $parsed,
            'Work/Projects/2025/Q1/Sprint1/test.md',
            'Work/Projects/2025/Q1/Sprint1'
        );

        expect($result->type)->toBe('project');
        expect($result->tags)->toContain('work');
        expect($result->tags)->toContain('projects');
        expect($result->tags)->toContain('2025');
        expect($result->tags)->toContain('q1');
        expect($result->tags)->toContain('sprint1');
    });

    it('handles special characters in tags gracefully', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test #tag-with-special!@#$%chars',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'test.md', null);

        expect($result->tags)->toBeArray();
        expect($result->tags)->toContain('obsidian');
    });

    it('handles non-string front matter type gracefully', function () {
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: ['type' => 123],
            tags: []
        );

        $result = $this->pipeline->process($parsed, 'Projects/test.md', 'Projects');

        expect($result->type)->toBe('project');
    });

    it('handles very long paths', function () {
        $longPath = str_repeat('folder/', 50).'file.md';
        $parsed = new ParsedObsidianNote(
            title: 'Test',
            body: '# Test',
            frontMatter: [],
            tags: []
        );

        $result = $this->pipeline->process($parsed, $longPath, null);

        expect($result)->toBeInstanceOf(EnrichedObsidianFragment::class);
        expect($result->tags)->toContain('folder');
    });
});
