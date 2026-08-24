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
        // La tabla tbl_solicitudes es gestionada por el proyecto administrador.
        // Se protege con hasColumn: la columna puede existir ya y no debe
        // fallar con columna duplicada.
        if (! Schema::hasTable('tbl_solicitudes') || Schema::hasColumn('tbl_solicitudes', 'fk_predio')) {
            return;
        }

        Schema::table('tbl_solicitudes', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_predio')->nullable()->after('fk_tramite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tbl_solicitudes')) {
            return;
        }

        Schema::table('tbl_solicitudes', function (Blueprint $table) {
            $table->dropColumn('fk_predio');
        });
    }
};
