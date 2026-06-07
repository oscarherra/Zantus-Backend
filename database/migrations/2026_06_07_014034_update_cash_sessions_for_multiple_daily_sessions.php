<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            // Quita la restricción que solo permite una caja por usuario por día
            $table->dropUnique('cash_sessions_user_id_date_unique');

            // Campos nuevos para calcular diferencias al cerrar caja
            $table->decimal('expected_amount', 12, 2)->nullable()->after('closing_amount');
            $table->decimal('difference_amount', 12, 2)->nullable()->after('expected_amount');

            // Índice útil para historial y filtros
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropIndex(['date', 'status']);

            $table->dropColumn([
                'expected_amount',
                'difference_amount',
            ]);

            $table->unique(['user_id', 'date']);
        });
    }
};