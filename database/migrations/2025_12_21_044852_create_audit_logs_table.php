<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('actor_user_id')->nullable()->constrained('users');
            $table->string('actor_role', 50)->nullable();

            $table->string('action', 120); // e.g. accounts.state_changed, tx.posted, scheduled.created
            $table->string('subject_type', 80)->nullable(); // accounts|transactions|scheduled|customers
            $table->uuid('subject_public_id')->nullable();

            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'created_at']);
            $table->index(['subject_public_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
