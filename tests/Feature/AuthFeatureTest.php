<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $data = []): User
    {
        $number = User::query()->count() + 1;

        return User::create(array_merge([
            'user_code' => 'DG'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'name' => 'Độc giả '.$number,
            'email' => "user{$number}@test.local",
            'password' => '12345678',
            'role' => 'user',
            'status' => 'active',
        ], $data));
    }

    public function test_registration_requires_valid_required_fields(): void
    {
        $this->post(route('register.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $existing = $this->user(['email' => 'duplicate@example.com']);

        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => $existing->email,
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_invalid_phone_when_phone_is_provided(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'phone@example.com',
            'phone' => 'abc123',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'phone@example.com']);
    }

    public function test_registration_generates_reader_code_and_hashes_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'new@example.com',
            'phone' => '0912345678',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertRedirect(route('books.index'));

        $user = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertSame('DG0001', $user->user_code);
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('12345678', $user->getRawOriginal('password')));
        $this->assertAuthenticatedAs($user);
    }

    public function test_active_user_can_login_and_logout(): void
    {
        $user = $this->user(['email' => 'login@example.com']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => '12345678',
        ])->assertRedirect(route('books.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', 'Đã đăng xuất.');

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->user(['email' => 'wrong-password@example.com']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_locked_and_deleted_users_cannot_login(): void
    {
        foreach (['locked', 'deleted'] as $status) {
            $user = $this->user([
                'email' => $status.'@example.com',
                'status' => $status,
            ]);

            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => '12345678',
            ])->assertSessionHasErrors('email');

            $this->assertGuest();
        }
    }

    public function test_admin_is_redirected_to_dashboard_after_login(): void
    {
        $admin = $this->user([
            'user_code' => 'NV0001',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => '12345678',
        ])->assertRedirect(route('statistics.index'));

        $this->assertAuthenticatedAs($admin);
    }
}
