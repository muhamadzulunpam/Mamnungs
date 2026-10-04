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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method');
            $table->string('provider');
            $table->string('transaction_id')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('amount_received')->nullable();
            $table->unsignedInteger('change_amount')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
