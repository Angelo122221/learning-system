<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaSharedAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_receive_shared_auth_data_on_the_resources_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('resources.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Resources/Index')
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->where('auth.user.role', $user->role)
                ->where('auth.user.district', $user->district)
                ->where('auth.user.school_name', $user->school_name)
            );
    }
}
