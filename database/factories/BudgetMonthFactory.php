<?php

namespace Database\Factories;

use App\Models\BudgetMonth;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetMonth>
 */
class BudgetMonthFactory extends Factory
{
    protected $model = BudgetMonth::class;

    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'month' => $this->faker->dateTimeThisYear(),
            'budgeted_amount' => $this->faker->randomFloat(2, 0, 5000),
            'realized_amount' => $this->faker->randomFloat(2, 0, 5000),
        ];
    }
}
