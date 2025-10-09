<?php

return [
    'resources' => ['fe.tasks', 'fe.runs', 'fe.artifacts'],
    'tools' => ['fe.run.start', 'fe.run.status', 'fe.artifact.put', 'fe.artifact.link'],
    'scopes' => [
        // agent_id => [scopes]
    ],
];
