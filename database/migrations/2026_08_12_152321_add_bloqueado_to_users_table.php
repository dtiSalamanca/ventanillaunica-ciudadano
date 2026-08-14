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
        // La columna ya es agregada por el proyecto administrador en la BD compartida.
        if (Schema::hasColumn('users', 'bloqueado')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('bloqueado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'bloqueado')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bloqueado');
        });
    }
};
