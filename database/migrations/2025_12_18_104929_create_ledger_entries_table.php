<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');

            $table->string('direction'); // debit | credit
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default(config('banking.currency', 'USD'));

            // للـaudit: قبل/بعد (مفيد جدًا)
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);

            $table->timestamps();

            $table->index(['transaction_id']);
            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
