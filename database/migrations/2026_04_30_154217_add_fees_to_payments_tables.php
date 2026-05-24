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
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->unsignedInteger('fees')->default(0)->after('amount');
        });

        Schema::table('guest_payments', function (Blueprint $table) {
            $table->unsignedInteger('fees')->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->dropColumn('fees');
        });

        Schema::table('guest_payments', function (Blueprint $table) {
            $table->dropColumn('fees');
        });
    }
};
