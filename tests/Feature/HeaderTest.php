<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_logo_and_title_link_to_home_and_are_not_selectable()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="'.url('/').'"', false);
        $response->assertSee('select-none', false);
    }
}
