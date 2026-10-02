<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'reader@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));

        $user = User::where('email', 'reader@example.com')->firstOrFail();
        $this->assertSame('DG0001', $user->user_code);
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_duplicate_email_and_mismatched_password(): void
    {
        User::create([
            'user_code' => 'DG0001',
            'name' => 'Người dùng cũ',
            'email' => 'reader@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'reader@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_locked_and_deleted_users_cannot_login(): void
    {
        foreach (['locked', 'deleted'] as $status) {
            $user = User::create([
                'user_code' => 'DG'.($status === 'locked' ? '0001' : '0002'),
                'name' => 'Độc giả',
                'email' => $status.'@example.com',
                'password' => 'password123',
                'role' => 'user',
                'status' => $status,
            ]);

            $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password123',
            ])->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');

            $this->assertGuest();
        }
    }

    public function test_admin_is_redirected_to_dashboard_after_login(): void
    {
        $admin = User::create([
            'user_code' => 'NV0001',
            'name' => 'Quản trị viên',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertRedirect(route('statistics.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::create([
            'user_code' => 'DG0001',
            'name' => 'Độc giả',
            'email' => 'reader@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('logout'))
            ->assertRedirect(route('books.index'));

        $this->assertGuest();
    }
}
