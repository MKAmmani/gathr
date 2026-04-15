<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->unsignedInteger('amount_due')->default(0);
            $table->unsignedInteger('amount_paid')->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['collection_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_participations');
    }
};
