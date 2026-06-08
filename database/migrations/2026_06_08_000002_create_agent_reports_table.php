<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('agent_id');
            $table->string('hostname');
            $table->timestamp('reported_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
            $table->index(['agent_id', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_reports');
    }
};
