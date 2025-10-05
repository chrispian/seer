<?php

return [
    'enabled_tools' => [
        'json.query'     => true,
        'text.search'    => true,
        'file.read'      => true,
        'text.replace'   => true,
        'help.index'     => true,
        'help.tool'      => true,
        'git.status'     => true,
        'git.diff'       => true,
        'git.apply_patch'=> true,
        'table.query'    => true,
    ],

    'priority_tools' => ['json.query', 'text.search', 'file.read', 'git.status', 'git.diff'],

    'categories' => [
        'JSON & Data' => ['json.query', 'table.query'],
        'Text Ops'    => ['text.search', 'text.replace'],
        'Files'       => ['file.read'],
        'Git'         => ['git.status', 'git.diff', 'git.apply_patch'],
        'Help'        => ['help.index', 'help.tool'],
    ],
];
