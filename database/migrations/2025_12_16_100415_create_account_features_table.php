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
        Schema::create('account_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();

            $table->enum('feature_type', ['overdraft', 'premium', 'insurance']);

            $table->json('config')->nullable();
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'feature_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_features');
    }
};
