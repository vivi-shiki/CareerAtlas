<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('profiles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->unique()->constrained();
            $t->string('display_name', 120);
            $t->string('direction', 240)->default('');
            $t->text('biography')->default('');
            $t->string('preferred_language', 5)->default('en');
            $t->jsonb('contact_links')->default('[]');
            $t->integer('version')->default(1);
            $t->timestampsTz();
        });
        Schema::create('projects', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('title', 200);
            $t->string('type', 30);
            $t->string('role', 160);
            $t->text('background')->default('');
            $t->text('team_outcome')->default('');
            $t->jsonb('occurrence')->nullable();
            $t->integer('version')->default(1);
            $t->timestampTz('archived_at')->nullable();
            $t->softDeletesTz();
            $t->timestampsTz();
            $t->unique(['id', 'owner_id']);
            $t->index(['owner_id', 'title']);
        });
        Schema::create('experiences', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->uuid('project_id');
            $t->foreign(['project_id', 'owner_id'])->references(['id', 'owner_id'])->on('projects');
            $t->string('title', 200)->nullable();
            $t->string('record_type', 40)->nullable();
            $t->jsonb('occurrence')->nullable();
            foreach (['personal_action', 'result', 'validation', 'remaining_work', 'private_notes'] as $field) {
                $t->text($field)->nullable();
            }
            $t->string('provenance', 30)->default('manual');
            $t->string('status', 20)->default('draft');
            $t->integer('version')->default(1);
            $t->uuid('confirmed_version_id')->nullable();
            $t->integer('access_epoch')->default(1);
            $t->timestampTz('archived_at')->nullable();
            $t->softDeletesTz();
            $t->timestampsTz();
            $t->index(['owner_id', 'project_id']);
            $t->unique(['id', 'owner_id']);
        });
        Schema::create('record_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('experience_id')->constrained();
            $t->integer('number');
            $t->jsonb('payload');
            $t->timestampTz('confirmed_at');
            $t->unique(['experience_id', 'number']);
        });
        Schema::table('experiences', fn (Blueprint $t) => $t->foreign('confirmed_version_id')->references('id')->on('record_versions'));
        Schema::create('skills', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('name', 100);
            $t->string('normalized_name', 100);
            $t->timestampsTz();
            $t->unique(['owner_id', 'normalized_name']);
            $t->unique(['id', 'owner_id']);
        });
        Schema::create('skill_aliases', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->uuid('skill_id');
            $t->foreign(['skill_id', 'owner_id'])->references(['id', 'owner_id'])->on('skills');
            $t->string('normalized_alias', 100);
            $t->unique(['owner_id', 'normalized_alias']);
            $t->timestampsTz();
        });
        Schema::create('experience_skill', function (Blueprint $t) {
            $t->foreignId('owner_id')->constrained('users');
            $t->uuid('experience_id');
            $t->uuid('skill_id');
            $t->foreign(['experience_id', 'owner_id'])->references(['id', 'owner_id'])->on('experiences');
            $t->foreign(['skill_id', 'owner_id'])->references(['id', 'owner_id'])->on('skills');
            $t->primary(['experience_id', 'skill_id']);
        });
        Schema::create('evidence', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('experience_id')->constrained();
            $t->string('kind', 30);
            $t->string('label', 200);
            $t->text('url')->nullable();
            $t->text('excerpt')->nullable();
            $t->string('provenance', 30);
            $t->integer('version')->default(1);
            $t->timestampsTz();
        });
        Schema::create('publications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('slug', 80)->unique();
            $t->string('state', 20);
            $t->integer('version')->default(1);
            $t->uuid('active_snapshot_id')->nullable();
            $t->timestampTz('published_at');
            $t->timestampTz('revoked_at')->nullable();
            $t->timestampsTz();
        });
        Schema::create('publication_snapshots', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('publication_id')->constrained();
            $t->integer('number');
            $t->jsonb('profile');
            $t->jsonb('selection');
            $t->timestampTz('created_at');
            $t->unique(['publication_id', 'number']);
        });
        Schema::table('publications', fn (Blueprint $t) => $t->foreign('active_snapshot_id')->references('id')->on('publication_snapshots'));
        Schema::create('publication_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('snapshot_id')->constrained('publication_snapshots');
            $t->foreignUuid('experience_id')->constrained();
            $t->foreignUuid('project_id')->constrained();
            $t->foreignUuid('record_version_id')->constrained();
            $t->integer('source_access_epoch');
            $t->integer('sort_order');
            $t->jsonb('payload');
            $t->unique(['snapshot_id', 'experience_id']);
        });
        Schema::create('idempotency_keys', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('operation', 80);
            $t->string('key', 120);
            $t->string('request_hash', 64);
            $t->jsonb('response');
            $t->timestampsTz();
            $t->unique(['owner_id', 'operation', 'key']);
        });
        Schema::create('audit_events', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('action', 80);
            $t->string('subject_type', 80);
            $t->uuid('subject_id')->nullable();
            $t->jsonb('metadata')->default('{}');
            $t->timestampTz('created_at');
        });
        foreach (['profiles', 'projects', 'experiences', 'evidence', 'publications'] as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_positive_version CHECK (version >= 1)");
        }
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_type_valid CHECK (type IN ('commercial','personal','learning'))");
        DB::statement("ALTER TABLE experiences ADD CONSTRAINT experiences_status_valid CHECK (status IN ('draft','confirmed'))");
        DB::statement("ALTER TABLE experiences ADD CONSTRAINT experiences_record_type_valid CHECK (record_type IS NULL OR record_type IN ('work_contribution','learning','problem_solving'))");
        DB::statement('ALTER TABLE experiences ADD CONSTRAINT experiences_epoch_valid CHECK (access_epoch >= 1)');
        DB::statement("ALTER TABLE publications ADD CONSTRAINT publications_state_valid CHECK (state IN ('published','revoked'))");
    }

    public function down(): void
    {
        Schema::table('publications', fn (Blueprint $t) => $t->dropForeign(['active_snapshot_id']));
        Schema::table('experiences', fn (Blueprint $t) => $t->dropForeign(['confirmed_version_id']));
        foreach (['audit_events', 'idempotency_keys', 'publication_items', 'publication_snapshots', 'publications', 'evidence', 'experience_skill', 'skill_aliases', 'skills', 'record_versions', 'experiences', 'projects', 'profiles', 'personal_access_tokens'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
