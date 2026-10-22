<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['articles', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('meta_title')->nullable()->after('excerpt');
                $table->string('meta_description', 320)->nullable()->after('meta_title');
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (['articles', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};
