<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected']);

            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users');

            $table->text('reason')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_approvals');
    }
};
