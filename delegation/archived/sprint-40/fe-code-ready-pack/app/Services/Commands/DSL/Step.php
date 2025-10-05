<?php

namespace App\Services\Commands\DSL;

interface Step
{
    /** @return mixed */
    public function execute(array $definition, array $scope);
}
