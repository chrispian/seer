<?php

namespace App\Services\Commands\DSL;

class FragmentCreateStep implements Step
{
    public function execute(array $def, array $scope)
    {
        // Validate against type schema here (omitted for brevity).
        // Persist to fragments table in your app.
        // Return a reference (id/ulid).
        return ['fragment_id' => 'demo-fragment-id'];
    }
}
