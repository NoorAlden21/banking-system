<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_transactions', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('owner_user_id')->constrained('users');
            $table->foreignId('created_by_user_id')->constrained('users');

            $table->enum('type', ['transfer', 'withdraw', 'deposit'])->default('transfer');

            // deposit: source nullable
            // withdraw: destination nullable
            $table->foreignId('source_account_id')
                ->nullable()
                ->constrained('accounts');

            $table->foreignId('destination_account_id')
                ->nullable()
                ->constrained('accounts');

            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default('USD'); // single currency

            $table->text('description')->nullable();

            // schedule rule
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->unsignedSmallInteger('interval')->default(1);

            // weekly: 0..6 (0=Sunday)
            $table->unsignedTinyInteger('day_of_week')->nullable();

            // monthly: 1..28
            $table->unsignedTinyInteger('day_of_month')->nullable();

            $table->time('run_time')->default('09:00:00');

            // استخدم dateTime بدل timestamp لتجنب مشكلة MySQL defaults
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            $table->enum('status', ['active', 'paused', 'canceled'])->default('active')->index();

            // runtime tracking
            $table->dateTime('next_run_at')->index();          // ✅ هنا أصل المشكلة
            $table->dateTime('last_run_at')->nullable();

            $table->uuid('last_transaction_public_id')->nullable();
            $table->string('last_status', 50)->nullable(); // posted | pending_approval | failed...
            $table->text('last_error')->nullable();
            $table->unsignedInteger('runs_count')->default(0);

            // locking
            $table->dateTime('locked_at')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_user_id', 'created_at']);
            $table->index(['status', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_transactions');
    }
};
