<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            $table->string('feature_key', 50); // overdraft | premium | insurance
            $table->enum('status', ['active', 'disabled'])->default('active')->index();

            $table->json('meta')->nullable(); // limit, fee_rate, monthly_fee, etc.

            $table->timestamps();

            $table->unique(['account_id', 'feature_key']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_features');
    }
};
