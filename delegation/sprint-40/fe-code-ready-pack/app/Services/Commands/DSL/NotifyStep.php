<?php

namespace App\Services\Commands\DSL;

class NotifyStep implements Step
{
    public function execute(array $def, array $scope)
    {
        // Record a system note/toast/log. For now, return message.
        return $def['with']['message'] ?? 'ok';
    }
}
