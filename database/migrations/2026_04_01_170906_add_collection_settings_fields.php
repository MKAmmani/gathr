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
        Schema::table('collections', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('allow_half_payment')->default(false);
            $table->boolean('anonymous_payments')->default(false);
            $table->boolean('organizer_pay_charges')->default(false);
            $table->boolean('allow_custom_amount')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['description', 'allow_half_payment', 'anonymous_payments', 'organizer_pay_charges', 'allow_custom_amount']);
        });
    }
};
