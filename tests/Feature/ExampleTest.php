<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_redirects_home_to_the_admin_panel(): void
    {
        $this->get('/')->assertRedirect('/admin/login');
    }
}
