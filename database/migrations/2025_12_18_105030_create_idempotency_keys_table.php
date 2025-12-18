<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('initiator_user_id')->constrained('users');

            $table->string('action'); // deposit | withdraw | transfer
            $table->string('idempotency_key');

            $table->char('request_hash', 64);

            // نقدر نخزن الرد النهائي حتى نرجعه فورًا عند إعادة نفس الطلب
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->longText('response_body')->nullable();

            $table->uuid('transaction_public_id')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();

            $table->unique(['initiator_user_id', 'action', 'idempotency_key'], 'idem_unique');
            $table->index(['initiator_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
