<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cat_tramites', function (Blueprint $table) {
            $table->integer('cuenta_predial', false, true, 11)->default(1)->after('precio_tramite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cat_tramites', function (Blueprint $table) {
            $table->dropColumn('cuenta_predial');
        });
    }
};
