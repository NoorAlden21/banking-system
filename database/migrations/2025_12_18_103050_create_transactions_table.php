<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('initiator_user_id')->constrained('users');

            $table->string('type');   // deposit | withdraw | transfer
            $table->string('status'); // posted | pending_approval | rejected | failed

            $table->foreignId('source_account_id')->nullable()->constrained('accounts');
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts');

            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default(config('banking.currency', 'USD'));

            $table->string('description')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('posted_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['initiator_user_id', 'created_at']);
            $table->index(['source_account_id', 'created_at']);
            $table->index(['destination_account_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
