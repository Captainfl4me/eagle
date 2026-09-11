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
        Schema::create('reallocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_budget_id')
                ->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('source_budget_id')
                ->constrained('budgets')->cascadeOnDelete();
            $table->date('month'); // first day of month
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['recipient_budget_id', 'source_budget_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reallocations');
    }
};
