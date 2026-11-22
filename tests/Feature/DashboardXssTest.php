<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for XSS via budget names rendered into JSON data
 * attributes on the dashboard (resources/views/dashboard.blade.php).
 *
 * The canvas data-labels / data-values attributes are single-quoted and
 * populated with @json($pie['labels']), which compiles to
 * json_encode(..., JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT).
 * Those flags escape quotes, angle brackets and ampersands (\u0027, \u003C,
 * \u0022, \u0026), so no attribute-delimiting character can appear raw in the
 * HTML attribute and an attacker-controlled budget name cannot break out.
 *
 * These tests assert, against the raw rendered HTML, that:
 *  - the name appears JSON-hex-escaped inside the attribute, and
 *  - no raw attribute-breaking sequence ("' onmouseover='", "<img", "onerror=")
 *   is present unescaped.
 */
class DashboardXssTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_quote_attribute_breakout_payload_is_escaped_in_dashboard_attributes(): void
    {
        $html = $this->renderDashboardWithBudget("x' onmouseover='alert(1)");

        // The JSON encoding must hex-escape the single quotes inside the attribute...
        $html->assertSee('\u0027 onmouseover=\u0027alert(1)', false);
        // ...so the raw breakout sequence must never appear unescaped in the HTML.
        $html->assertDontSee("' onmouseover='", false);
    }

    public function test_double_quote_tag_breakout_payload_is_escaped_in_dashboard_attributes(): void
    {
        $html = $this->renderDashboardWithBudget('"><img src=x onerror=alert(1)>');

        // Quotes and angle brackets must be JSON-escaped (\u0022, \u003C)...
        $html->assertSee('\u0022\u003E\u003Cimg', false);
        // ...so no raw markup can be smuggled into the attribute.
        $html->assertDontSee('"><img', false);
        $html->assertDontSee('<img src=x', false);
    }

    /**
     * Render the dashboard for a user owning one budget with the given name
     * and a non-empty allocation, so the pie chart data attributes are emitted.
     */
    private function renderDashboardWithBudget(string $name)
    {
        $user = User::factory()->create();

        $budget = $user->budgets()->create([
            'name' => $name,
            'start_month' => '2026-01-01',
            'start_amount' => 100,
        ]);

        $budget->months()->create([
            'month' => '2026-01-01',
            'budgeted_amount' => 100,
            'realized_amount' => 0,
        ]);

        return $this->actingAs($user)->get('/');
    }
}
