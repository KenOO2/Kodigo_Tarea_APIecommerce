<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('stripe_payment_intent_id')->unique();
            $table->string('stripe_client_secret')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('usd');
            $table->string('status')->default('requires_payment_method');
            $table->string('payment_method')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
