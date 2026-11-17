<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capture_drafts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->uuid('project_id');
            $t->foreign(['project_id', 'owner_id'])->references(['id', 'owner_id'])->on('projects');
            $t->text('notes');
            $t->string('target_language', 5)->default('en');
            $t->jsonb('draft')->default('{}');
            $t->string('provenance', 20)->default('manual');
            $t->jsonb('questions')->default('[]');
            $t->foreignUuid('applied_experience_id')->nullable()->constrained('experiences');
            $t->integer('version')->default(1);
            $t->timestampsTz();
        });
        Schema::create('experience_revisions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('experience_id')->constrained();
            $t->integer('number');
            $t->integer('source_version');
            $t->string('event', 80);
            $t->jsonb('payload');
            $t->string('digest', 64);
            $t->timestampTz('created_at');
            $t->unique(['experience_id', 'number']);
        });
        Schema::create('preparations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('topic', 200);
            $t->string('company', 200)->default('');
            $t->date('interview_date')->nullable();
            $t->text('requirements')->default('');
            $t->text('source_url')->nullable();
            $t->string('target_language', 5)->default('en');
            $t->string('answer_length', 20)->default('standard');
            $t->jsonb('sources')->default('[]');
            $t->integer('version')->default(1);
            $t->timestampsTz();
            $t->unique(['id', 'owner_id']);
        });
        Schema::create('prepared_answers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->uuid('preparation_id');
            $t->foreign(['preparation_id', 'owner_id'])->references(['id', 'owner_id'])->on('preparations');
            $t->text('question');
            $t->text('answer')->default('');
            $t->jsonb('sources')->default('[]');
            $t->string('target_language', 5);
            $t->string('state', 20)->default('draft');
            $t->integer('version')->default(1);
            $t->timestampTz('confirmed_at')->nullable();
            $t->timestampsTz();
        });
        Schema::create('answer_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('answer_id')->constrained('prepared_answers');
            $t->integer('number');
            $t->jsonb('payload');
            $t->timestampTz('created_at');
            $t->unique(['answer_id', 'number']);
        });
        Schema::create('practice_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->uuid('preparation_id');
            $t->foreign(['preparation_id', 'owner_id'])->references(['id', 'owner_id'])->on('preparations');
            $t->jsonb('questions');
            $t->jsonb('attempts')->default('[]');
            $t->jsonb('sources')->default('[]');
            $t->integer('current_index')->default(0);
            $t->string('state', 20)->default('active');
            $t->integer('version')->default(1);
            $t->boolean('retained')->default(false);
            $t->timestampTz('expires_at')->nullable();
            $t->timestampsTz();
        });
        Schema::create('opportunities', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('title', 200);
            $t->string('direction', 200);
            $t->text('requirements')->default('');
            $t->date('deadline')->nullable();
            $t->string('target_language', 5)->default('en');
            $t->text('introduction')->default('');
            $t->boolean('include_introduction')->default(false);
            $t->boolean('introduction_confirmed')->default(false);
            $t->jsonb('sources')->default('[]');
            $t->jsonb('selection')->default('{"profile_fields":[],"items":[]}');
            $t->foreignUuid('publication_id')->nullable()->unique()->constrained('publications');
            $t->integer('version')->default(1);
            $t->timestampTz('archived_at')->nullable();
            $t->timestampsTz();
        });
        Schema::table('publication_snapshots', function (Blueprint $t) {
            $t->text('introduction')->default('');
            $t->jsonb('introduction_sources')->default('[]');
        });
        Schema::create('owner_ai_runs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('purpose', 30);
            $t->uuid('subject_id');
            $t->integer('subject_version');
            $t->uuid('request_id');
            $t->jsonb('context');
            $t->string('context_digest', 64);
            $t->jsonb('output')->nullable();
            $t->string('state', 20)->default('queued');
            $t->string('error_code', 40)->nullable();
            $t->timestampTz('deadline_at');
            $t->timestampsTz();
            $t->unique(['owner_id', 'request_id']);
            $t->index(['owner_id', 'created_at']);
        });
        foreach (['capture_drafts', 'preparations', 'prepared_answers', 'practice_sessions', 'opportunities'] as $table) {
            DB::statement("ALTER TABLE $table ADD CONSTRAINT {$table}_positive_version CHECK (version >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_ai_runs');
        Schema::dropIfExists('opportunities');
        Schema::table('publication_snapshots', fn (Blueprint $t) => $t->dropColumn(['introduction', 'introduction_sources']));
        foreach (['practice_sessions', 'answer_versions', 'prepared_answers', 'preparations', 'experience_revisions', 'capture_drafts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
