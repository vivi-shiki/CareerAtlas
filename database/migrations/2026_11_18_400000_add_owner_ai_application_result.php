<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_ai_runs', function (Blueprint $t) {
            $t->jsonb('application_result')->nullable();
            $t->string('request_hash', 64)->default('');
        });
    }

    public function down(): void
    {
        Schema::table('owner_ai_runs', function (Blueprint $t) {
            $t->dropColumn(['application_result', 'request_hash']);
        });
    }
};
