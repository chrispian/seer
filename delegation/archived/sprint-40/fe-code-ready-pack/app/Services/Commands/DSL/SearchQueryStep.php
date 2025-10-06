<?php

namespace App\Services\Commands\DSL;

class SearchQueryStep implements Step
{
    public function execute(array $def, array $scope)
    {
        // Wire to your recall/search service.
        return ['count' => 0, 'items' => []];
    }
}
