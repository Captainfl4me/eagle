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
        // Change the month column to a date type (no time component)
        Schema::table('budget_months', function (Blueprint $table) {
            $table->date('month')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_months', function (Blueprint $table) {
            $table->dateTime('month')->change();
        });
    }
};
