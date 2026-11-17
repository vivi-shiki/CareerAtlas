<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('principal', 64)->index();
            $table->string('slug');
            $table->string('scope_hash', 64);
            $table->timestampTz('created_at');
            $table->timestampTz('expires_at')->index();
        });
        Schema::create('agent_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('agent_conversations')->cascadeOnDelete();
            $table->uuid('request_id');
            $table->text('question');
            $table->string('state', 20)->default('queued');
            $table->string('progress', 40)->default('Queued');
            $table->jsonb('answer')->nullable();
            $table->string('error_code', 40)->nullable();
            $table->jsonb('tools')->default('[]');
            $table->jsonb('usage')->default('{}');
            $table->timestampTz('created_at');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampTz('deadline_at');
            $table->unique(['conversation_id', 'request_id']);
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
        Schema::dropIfExists('agent_conversations');
    }
};
