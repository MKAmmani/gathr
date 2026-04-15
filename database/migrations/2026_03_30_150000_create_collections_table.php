<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('contribution_amount')->default(0);
            $table->unsignedInteger('target_amount')->nullable();
            $table->unsignedInteger('participant_goal')->default(0);
            $table->string('status')->default('active');
            $table->string('type')->default('fixed');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
