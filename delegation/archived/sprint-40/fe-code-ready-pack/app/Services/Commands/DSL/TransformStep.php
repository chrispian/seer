<?php

namespace App\Services\Commands\DSL;

class TransformStep implements Step
{
    public function execute(array $def, array $scope)
    {
        $tpl = $def['template'] ?? '';

        return Templating::render($tpl, array_merge($scope, $scope['steps'] ?? []));
    }
}
