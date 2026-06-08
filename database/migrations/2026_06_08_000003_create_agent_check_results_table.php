<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('agent_reports')->cascadeOnDelete();
            $table->string('name'); // php, mysql, reverb, redis
            $table->string('status'); // ok, warning, critical, unknown
            $table->string('message');
            $table->json('metrics')->nullable();
            $table->timestamp('checked_at');

            $table->index(['report_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_check_results');
    }
};
