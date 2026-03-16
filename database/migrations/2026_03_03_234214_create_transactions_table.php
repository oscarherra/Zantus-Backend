<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['income', 'expense']);
            $table->string('category')->default('General');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash'); // cash/sinpe/card
            $table->string('description')->nullable();

            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();

            $table->index(['type', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};