<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root hands off by auth state, so a guest gets a redirect rather than
     * a rendered page. The signed-in branch is covered by RoleAwareNavigationTest.
     */
    public function test_the_application_root_redirects_a_guest_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    /**
     * Following that redirect has to actually render the sign-in screen.
     */
    public function test_the_login_screen_renders_for_a_guest(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to your account')
            ->assertSee('stockify-logo');
    }
}