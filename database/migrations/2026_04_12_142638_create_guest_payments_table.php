<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->string('transaction_reference')->unique(); // Monnify transaction ref
            $table->string('payment_reference')->unique(); // Our payment ref
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->unsignedInteger('amount');
            $table->boolean('is_anonymous')->default(false);
            $table->string('payment_type'); // full, half, custom
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_payments');
    }
};
