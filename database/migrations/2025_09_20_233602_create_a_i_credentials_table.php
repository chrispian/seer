<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('a_i_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('credential_type')->default('api_key'); // api_key, oauth_token, etc.
            $table->text('encrypted_credentials'); // JSON encrypted credentials
            $table->json('metadata')->nullable(); // Non-sensitive metadata
            $table->timestamp('expires_at')->nullable(); // For OAuth tokens
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider', 'credential_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_credentials');
    }
};
