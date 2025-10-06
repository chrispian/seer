<?php

return [
    'enabled_tools' => [
        'json.query'   => true,
        'text.search'  => true,
        'file.read'    => true,
        'text.replace' => true,
        'help.index'   => true,
        'help.tool'    => true,
    ],
    'priority_tools' => [
        'json.query',
        'text.search',
        'file.read',
    ],
    'categories' => [
        'JSON & Data' => ['json.query', 'table.query'],
        'Text Ops'    => ['text.search', 'text.replace'],
        'Files'       => ['file.read'],
        'Help'        => ['help.index', 'help.tool'],
    ],
];
