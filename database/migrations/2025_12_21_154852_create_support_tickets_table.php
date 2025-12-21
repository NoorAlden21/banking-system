<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('owner_user_id')->constrained('users');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');

            $table->string('subject', 150);

            $table->enum('category', ['account', 'transaction', 'technical', 'other'])->nullable();
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');

            $table->enum('status', [
                'open',
                'pending_staff',
                'pending_customer',
                'resolved',
                'closed',
            ])->default('open')->index();

            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_user_id', 'created_at']);
            $table->index(['assigned_to_user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
