<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_can_be_selected_with_a_month_input()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('budgets.show', ['id' => $budget->id, 'month' => '2024-03']));

        $response->assertStatus(200);
        $response->assertSee('type="month"', false);
        $response->assertSee('value="2024-03"', false);
        $response->assertSee('March 2024');
    }

    public function test_invalid_month_param_falls_back_to_default_month()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('budgets.show', ['id' => $budget->id, 'month' => 'not-a-month']));

        $response->assertStatus(200);
        $response->assertSee('January 2024');
    }

    public function test_month_before_start_month_is_clamped_to_start_month()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('budgets.show', ['id' => $budget->id, 'month' => '2023-05']));

        $response->assertStatus(200);
        $response->assertSee('value="2024-01"', false);
        $response->assertSee('January 2024');
    }
}
