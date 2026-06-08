<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->text('public_key');
            $table->string('fingerprint')->unique();
            $table->enum('status', ['pending', 'active', 'revoked'])->default('pending');

            // Per-check configuration stored as JSON
            $table->json('php_config')->nullable();
            $table->json('mysql_config')->nullable();
            $table->json('reverb_config')->nullable();
            $table->json('redis_config')->nullable();

            $table->unsignedInteger('check_interval')->default(60);
            $table->unsignedInteger('config_poll_interval')->default(300);
            $table->unsignedBigInteger('config_version')->default(1);

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
