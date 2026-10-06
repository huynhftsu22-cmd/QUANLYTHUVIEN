<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kiểm thử module của Người 3: UC08 (tác giả/thể loại) và UC10 (người dùng).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['user_code' => 'NV0001', 'name' => 'Admin', 'email' => 'admin@test.local', 'password' => '12345678', 'role' => 'admin', 'status' => 'active']);
    }

    private function reader(string $status = 'active'): User
    {
        return User::create(['user_code' => 'DG0001', 'name' => 'Độc giả', 'email' => 'reader@test.local', 'password' => '12345678', 'role' => 'user', 'status' => $status]);
    }

    public function test_deleted_account_cannot_be_locked_or_unlocked(): void
    {
        $admin = $this->admin();
        $reader = $this->reader('deleted');

        $this->actingAs($admin)->patch(route('users.lock', $reader))->assertSessionHas('error');
        $this->actingAs($admin)->patch(route('users.unlock', $reader))->assertSessionHas('error');
        $this->assertSame('deleted', $reader->fresh()->status);
    }

    public function test_lock_unlock_and_soft_delete_reader(): void
    {
        $admin = $this->admin();
        $reader = $this->reader();

        $this->actingAs($admin)->patch(route('users.lock', $reader))->assertSessionHas('success');
        $this->assertSame('locked', $reader->fresh()->status);
        $this->actingAs($admin)->patch(route('users.unlock', $reader))->assertSessionHas('success');
        $this->assertSame('active', $reader->fresh()->status);
        $this->actingAs($admin)->delete(route('users.destroy', $reader))->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $reader->id, 'status' => 'deleted']);
    }

    public function test_admin_cannot_lock_or_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('users.lock', $admin))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHas('error');
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_update_ignores_user_code_and_role_and_rejects_duplicate_email(): void
    {
        $admin = $this->admin();
        $reader = $this->reader();

        $this->actingAs($admin)->put(route('users.update', $reader), ['name' => 'Tên mới', 'email' => 'new@test.local', 'user_code' => 'XX9999', 'role' => 'admin'])->assertSessionHasNoErrors();
        $reader->refresh();
        $this->assertSame(['Tên mới', 'DG0001', 'user'], [$reader->name, $reader->user_code, $reader->role]);

        $this->actingAs($admin)->put(route('users.update', $reader), ['name' => 'Tên mới', 'email' => 'admin@test.local'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->put(route('users.update', $reader), ['name' => 'Tên mới', 'email' => 'sai-dinh-dang'])->assertSessionHasErrors('email');
    }

    public function test_author_and_category_referenced_by_hidden_book_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $author = Author::create(['name' => 'Tác giả']);
        $category = Category::create(['name' => 'Thể loại']);
        Book::create(['book_code' => 'S0001', 'title' => 'Sách ẩn', 'author_id' => $author->id, 'category_id' => $category->id, 'total_quantity' => 1, 'available' => 1, 'is_active' => false]);

        $this->actingAs($admin)->delete(route('authors.destroy', $author))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('categories.destroy', $category))->assertSessionHas('error');
        $this->assertModelExists($author);
        $this->assertModelExists($category);
    }

    public function test_unreferenced_author_and_category_can_be_deleted(): void
    {
        $admin = $this->admin();
        $author = Author::create(['name' => 'Tác giả']);
        $category = Category::create(['name' => 'Thể loại']);

        $this->actingAs($admin)->delete(route('authors.destroy', $author))->assertSessionHas('success');
        $this->actingAs($admin)->delete(route('categories.destroy', $category))->assertSessionHas('success');
        $this->assertModelMissing($author);
        $this->assertModelMissing($category);
    }

    public function test_category_name_must_be_unique_and_author_name_required(): void
    {
        $admin = $this->admin();
        Category::create(['name' => 'Thiếu nhi']);

        $this->actingAs($admin)->post(route('categories.store'), ['name' => 'Thiếu nhi'])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post(route('authors.store'), ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_reader_cannot_access_user_management(): void
    {
        $reader = $this->reader();

        $this->actingAs($reader)->get(route('users.index'))->assertForbidden();
        $this->actingAs($reader)->get(route('authors.index'))->assertForbidden();
        $this->actingAs($reader)->get(route('categories.index'))->assertForbidden();
    }

    public function test_admin_creates_accounts_with_auto_generated_code_by_role(): void
    {
        $admin = $this->admin();
        $this->reader();
        $base = ['phone' => '0901234567', 'password' => '12345678', 'password_confirmation' => '12345678'];

        $this->actingAs($admin)->post(route('users.store'), $base + ['name' => 'Độc giả mới', 'email' => 'docgia@test.local', 'role' => 'user', 'user_code' => 'XX0001'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('users.store'), $base + ['name' => 'Nhân viên mới', 'email' => 'nhanvien@test.local', 'role' => 'admin'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'docgia@test.local', 'user_code' => 'DG0002', 'role' => 'user', 'status' => 'active']);
        $this->assertDatabaseHas('users', ['email' => 'nhanvien@test.local', 'user_code' => 'NV0002', 'role' => 'admin', 'status' => 'active']);
        $this->assertNotSame('12345678', User::where('email', 'docgia@test.local')->value('password'));
    }

    public function test_create_account_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('users.store'), ['name' => '', 'email' => 'admin@test.local', 'role' => 'boss', 'password' => '123', 'password_confirmation' => '456'])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
        $this->assertSame(1, User::count());
    }
}
