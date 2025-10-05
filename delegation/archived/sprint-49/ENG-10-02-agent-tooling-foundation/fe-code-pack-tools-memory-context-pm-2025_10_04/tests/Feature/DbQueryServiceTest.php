<?php

uses(Tests\TestCase::class);

it('runs db.query without crashing', function () {
    $res = app(\App\Services\DbQueryService::class)->run([
        'entity' => 'work_items',
        'filters' => [],
        'limit' => 1,
        'offset' => 0,
    ]);
    expect($res)->toBeArray();
});
