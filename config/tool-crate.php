<?php

return [
    'enabled_tools' => [
        'json_query'   => true,
        'text_search'  => true,
        'file_read'    => true,
        'text_replace' => true,
        'help_index'   => true,
        'help_tool'    => true,
    ],
    'priority_tools' => [
        'json_query',
        'text_search',
        'file_read',
    ],
    'categories' => [
        'JSON & Data' => ['json_query', 'table_query'],
        'Text Ops'    => ['text_search', 'text_replace'],
        'Files'       => ['file_read'],
        'Help'        => ['help_index', 'help_tool'],
    ],
];
