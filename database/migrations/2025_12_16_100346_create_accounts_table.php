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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->enum('type', ['group', 'savings', 'checking', 'loan', 'investment']);
            $table->enum('state', ['active', 'frozen', 'suspended', 'closed'])->default('active');

            $table->decimal('balance', 18, 2)->default(0);

            $table->decimal('daily_limit', 18, 2)->nullable();
            $table->decimal('monthly_limit', 18, 2)->nullable();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
            $table->index(['parent_id']);
            $table->index(['state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
