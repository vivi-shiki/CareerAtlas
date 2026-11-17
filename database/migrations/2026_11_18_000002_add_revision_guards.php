<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', fn (Blueprint $t) => $t->integer('version')->default(1));
        Schema::table('publication_snapshots', fn (Blueprint $t) => $t->jsonb('source_versions')->default('{}'));
    }

    public function down(): void
    {
        Schema::table('publication_snapshots', fn (Blueprint $t) => $t->dropColumn('source_versions'));
        Schema::table('skills', fn (Blueprint $t) => $t->dropColumn('version'));
    }
};
