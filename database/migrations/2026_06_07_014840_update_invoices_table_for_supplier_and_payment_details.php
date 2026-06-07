<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('invoices', 'description')) {
                $table->text('description')
                    ->nullable()
                    ->after('category_name');
            }

            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')
                    ->nullable()
                    ->after('description');
            }

            if (!Schema::hasColumn('invoices', 'paid_by')) {
                $table->foreignId('paid_by')
                    ->nullable()
                    ->after('paid_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }

            if (Schema::hasColumn('invoices', 'issue_date')) {
                $table->dropColumn('issue_date');
            }

            if (Schema::hasColumn('invoices', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('invoices', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }
        });
    }
};