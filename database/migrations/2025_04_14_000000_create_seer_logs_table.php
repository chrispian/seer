<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        public function up(): void {
            Schema::create('seer_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->text('message');
                $table->json('tags')->nullable();
                $table->json('relationships')->nullable();
                $table->timestamps();
            });
        }

        public function down(): void {
            Schema::dropIfExists('seer_logs');
        }
    };