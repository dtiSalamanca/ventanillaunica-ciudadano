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
        // La tabla cat_tramites es gestionada por el proyecto administrador.
        if (! Schema::hasTable('cat_tramites')) {
            return;
        }

        Schema::table('cat_tramites', function (Blueprint $table) {
            $table->integer('cuenta_predial', false, true, 11)->default(1)->after('precio_tramite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cat_tramites')) {
            return;
        }

        Schema::table('cat_tramites', function (Blueprint $table) {
            $table->dropColumn('cuenta_predial');
        });
    }
};
