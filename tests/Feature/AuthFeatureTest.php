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

    // ---------- UC02 – Đăng ký ----------

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
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_generates_reader_code_and_hashes_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'new@example.com',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ])->assertRedirect(route('books.index'));

        $user = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertSame('DG0001', $user->user_code);
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('Matkhau123', $user->getRawOriginal('password')));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_saves_optional_phone(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'contact@example.com',
            'phone' => '0901234567',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'contact@example.com',
            'phone' => '0901234567',
        ]);
    }

    public function test_registration_allows_empty_phone(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'nophone@example.com',
            'phone' => '',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ])->assertRedirect(route('books.index'));

        $this->assertNull(User::where('email', 'nophone@example.com')->firstOrFail()->phone);
    }

    public function test_registration_rejects_invalid_phone_numbers(): void
    {
        foreach (['abc', '123', '0123456789', '090123456', '09012345678', '+8490123', '0901 234 567'] as $i => $phone) {
            $this->post(route('register.store'), [
                'name' => 'Người mới',
                'email' => "badphone{$i}@example.com",
                'phone' => $phone,
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
            ])->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_accepts_valid_vietnamese_phone_numbers(): void
    {
        foreach (['0901234567', '0381234567', '0771234567'] as $i => $phone) {
            $this->post(route('register.store'), [
                'name' => 'Người mới',
                'email' => "goodphone{$i}@example.com",
                'phone' => $phone,
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('users', ['email' => "goodphone{$i}@example.com", 'phone' => $phone]);
            $this->post(route('logout'));
        }
    }

    public function test_phone_with_plus84_is_stored_in_zero_format(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'plus84@example.com',
            'phone' => '+84901234567',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'plus84@example.com', 'phone' => '0901234567']);
    }

    public function test_registration_rejects_duplicate_phone(): void
    {
        $this->user(['phone' => '0901234567']);

        foreach (['0901234567', '+84901234567'] as $i => $phone) {
            $this->post(route('register.store'), [
                'name' => 'Người mới',
                'email' => "dupphone{$i}@example.com",
                'phone' => $phone,
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
            ])->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_allows_many_accounts_without_phone(): void
    {
        foreach (['a', 'b'] as $prefix) {
            $this->post(route('register.store'), [
                'name' => 'Độc giả '.$prefix,
                'email' => $prefix.'@example.com',
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
            ])->assertSessionHasNoErrors();
            $this->post(route('logout'));
        }

        $this->assertDatabaseCount('users', 2);
    }

    public function test_registration_saves_address_from_hcm_ward_list(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Người mới',
            'email' => 'address@example.com',
            'address' => 'Phường Bến Thành',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'address@example.com', 'address' => 'Phường Bến Thành']);
    }

    public function test_registration_rejects_address_outside_ward_list(): void
    {
        foreach (['Phường Không Tồn Tại', 'Quận 1', '123 Nguyễn Huệ'] as $i => $address) {
            $this->post(route('register.store'), [
                'name' => 'Người mới',
                'email' => "badaddress{$i}@example.com",
                'address' => $address,
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
            ])->assertSessionHasErrors('address');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_cannot_set_duplicate_phone_or_invalid_address_when_editing_user(): void
    {
        $admin = $this->user(['user_code' => 'NV0001', 'role' => 'admin']);
        $first = $this->user(['phone' => '0901234567']);
        $second = $this->user(['phone' => '0912345678']);

        $this->actingAs($admin)
            ->put(route('users.update', $second), ['name' => $second->name, 'email' => $second->email, 'phone' => '+84901234567'])
            ->assertSessionHasErrors('phone');

        $this->actingAs($admin)
            ->put(route('users.update', $second), ['name' => $second->name, 'email' => $second->email, 'phone' => '0912345678', 'address' => 'Nơi nào đó'])
            ->assertSessionHasErrors('address');

        // Giữ nguyên SĐT của chính mình và chọn phường hợp lệ thì lưu được.
        $this->actingAs($admin)
            ->put(route('users.update', $second), ['name' => $second->name, 'email' => $second->email, 'phone' => '0912345678', 'address' => 'Phường Gò Vấp'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $second->id, 'address' => 'Phường Gò Vấp']);
        $this->assertSame('0901234567', $first->fresh()->phone);
    }

    public function test_registration_rejects_weak_passwords(): void
    {
        foreach (['12345678', 'abcdefgh', 'ABCDEFGH1', 'abcdefg1', 'Abcdefgh', 'Ab1'] as $i => $password) {
            $this->post(route('register.store'), [
                'name' => 'Người mới',
                'email' => "weak{$i}@example.com",
                'password' => $password,
                'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_cannot_set_user_code_role_or_status(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Kẻ tấn công',
            'email' => 'hacker@example.com',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
            'user_code' => 'NV9999',
            'role' => 'admin',
            'status' => 'locked',
        ])->assertRedirect(route('books.index'));

        $user = User::where('email', 'hacker@example.com')->firstOrFail();

        $this->assertSame('DG0001', $user->user_code);
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->status);
    }

    public function test_user_codes_increase_for_each_new_reader(): void
    {
        foreach (['a', 'b'] as $i => $prefix) {
            $this->post(route('register.store'), [
                'name' => 'Độc giả '.$prefix,
                'email' => $prefix.'@example.com',
                'password' => 'Matkhau123',
                'password_confirmation' => 'Matkhau123',
            ]);
            $this->post(route('logout'));
        }

        $this->assertSame('DG0001', User::where('email', 'a@example.com')->value('user_code'));
        $this->assertSame('DG0002', User::where('email', 'b@example.com')->value('user_code'));
    }

    // ---------- UC03 – Đăng nhập / Đăng xuất ----------

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

    public function test_login_requires_email_and_password(): void
    {
        $this->post(route('login.store'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

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

    // ---------- Phân quyền / BR-09 (phiên cũ) ----------

    public function test_locked_user_with_existing_session_is_logged_out(): void
    {
        $user = $this->user(['email' => 'session@example.com']);
        $this->actingAs($user);

        $user->update(['status' => 'locked']);

        $this->get(route('history.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reader_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->user(['email' => 'reader@example.com']));

        $this->get(route('statistics.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_admin_pages(): void
    {
        $this->get(route('statistics.index'))->assertRedirect(route('login'));
    }
}
