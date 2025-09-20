<?php

use App\Models\Fragment;
use App\Models\Type;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate type_id based on existing type values
        Fragment::whereNotNull('type')->chunk(100, function ($fragments) {
            foreach ($fragments as $fragment) {
                $type = Type::where('value', $fragment->type)->first();
                if ($type) {
                    $fragment->update(['type_id' => $type->id]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set type_id back to null
        Fragment::whereNotNull('type_id')->update(['type_id' => null]);
    }
};
