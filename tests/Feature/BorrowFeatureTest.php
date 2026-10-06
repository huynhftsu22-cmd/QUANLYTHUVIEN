<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Category;
use App\Models\User;
use App\Services\BorrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Kiểm thử module Mượn – Trả (Người 4): UC04 Mượn sách, UC06 Trả sách,
 * UC09 Quản lý phiếu mượn – trả, cùng các quy tắc BR-01 → BR-07, BR-09, BR-13.
 */
class BorrowFeatureTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ helpers

    private function reader(array $data = []): User
    {
        $n = ++self::$sequence;

        return User::create(array_merge([
            'user_code' => 'DG'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'name' => 'Độc giả '.$n,
            'email' => "reader{$n}@borrow.test",
            'password' => '12345678',
            'role' => 'user',
            'status' => 'active',
        ], $data));
    }

    private function admin(): User
    {
        $n = ++self::$sequence;

        return User::create([
            'user_code' => 'NV'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'name' => 'Quản trị '.$n,
            'email' => "admin{$n}@borrow.test",
            'password' => '12345678',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function book(array $data = []): Book
    {
        $n = ++self::$sequence;
        $author = Author::create(['name' => 'Tác giả '.$n]);
        $category = Category::create(['name' => 'Thể loại '.$n]);

        return Book::create(array_merge([
            'book_code' => 'S'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'title' => 'Sách '.$n,
            'author_id' => $author->id,
            'category_id' => $category->id,
            'total_quantity' => 2,
            'available' => 2,
            'is_active' => true,
        ], $data));
    }

    private function record(User $user, Book $book, array $data = []): BorrowRecord
    {
        return BorrowRecord::create(array_merge([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'borrow_date' => today(),
            'due_date' => today()->addDays(14),
            'status' => 'borrowing',
            'fine_amount' => 0,
            'is_paid' => true,
        ], $data));
    }

    /** Khẳng định thao tác bị từ chối bằng ValidationException với đúng thông báo. */
    private function assertRejected(callable $action, string $field, string $message): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->assertSame($message, $e->errors()[$field][0] ?? null);

            return;
        }
        $this->fail('Thao tác lẽ ra phải bị từ chối: '.$message);
    }

    /** @return list<int> id các phiếu đang hiển thị trên trang, đã sắp xếp. */
    private function listedIds($response): array
    {
        return $response->viewData('records')->getCollection()->pluck('id')->sort()->values()->all();
    }

    // ------------------------------------------------------------ UC04 – Mượn sách

    public function test_uc04_borrow_creates_record_with_14_day_due_date_and_decrements_available(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');
        $reader = $this->reader();
        $book = $this->book();

        $this->actingAs($reader)->from('/books/'.$book->id)
            ->post(route('borrow.store', $book))
            ->assertRedirect('/books/'.$book->id)
            ->assertSessionHas('success', 'Mượn sách thành công. Hạn trả là 15/09/2026.');

        $this->assertDatabaseHas('borrow_records', [
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'borrow_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'return_date' => null,
            'status' => 'borrowing',
            'fine_amount' => 0,
            'is_paid' => 1,
        ]);
        $this->assertSame(1, $book->fresh()->available);
    }

    public function test_uc04_guest_is_redirected_to_login_and_admin_is_forbidden(): void
    {
        $book = $this->book();

        $this->post(route('borrow.store', $book))->assertRedirect(route('login'));
        $this->actingAs($this->admin())->post(route('borrow.store', $book))->assertForbidden();

        $this->assertDatabaseCount('borrow_records', 0);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc04_locked_account_with_old_session_is_logged_out_and_cannot_borrow(): void
    {
        $book = $this->book();
        $locked = $this->reader(['status' => 'locked']);

        $this->actingAs($locked)->post(route('borrow.store', $book))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Tài khoản đã bị khóa hoặc vô hiệu hóa.');

        $this->assertGuest();
        $this->assertDatabaseCount('borrow_records', 0);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc04_service_rejects_locked_and_deleted_accounts(): void
    {
        $book = $this->book();

        foreach (['locked', 'deleted'] as $status) {
            $this->assertRejected(
                fn () => app(BorrowService::class)->borrow($this->reader(['status' => $status]), $book),
                'book',
                'Tài khoản không hoạt động nên không thể mượn sách.'
            );
        }

        $this->assertDatabaseCount('borrow_records', 0);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc04_hidden_or_out_of_stock_book_is_rejected_without_changing_stock(): void
    {
        $reader = $this->reader();
        $hidden = $this->book(['is_active' => false]);
        $soldOut = $this->book(['total_quantity' => 1, 'available' => 0]);

        foreach ([$hidden, $soldOut] as $book) {
            $this->actingAs($reader)->from('/books/'.$book->id)
                ->post(route('borrow.store', $book))
                ->assertRedirect('/books/'.$book->id)
                ->assertSessionHasErrors(['book' => 'Sách đã hết hoặc đang bị ẩn.']);
        }

        $this->assertDatabaseCount('borrow_records', 0);
        $this->assertSame(2, $hidden->fresh()->available);
        $this->assertSame(0, $soldOut->fresh()->available);
    }

    public function test_uc04_duplicate_unreturned_loan_is_rejected_for_borrowing_and_overdue(): void
    {
        $reader = $this->reader();
        $book = $this->book(['total_quantity' => 3, 'available' => 3]);

        $this->actingAs($reader)->post(route('borrow.store', $book))->assertSessionHas('success');
        $this->assertSame(2, $book->fresh()->available);

        $this->actingAs($reader)->post(route('borrow.store', $book))
            ->assertSessionHasErrors(['book' => 'Bạn đang mượn cuốn sách này và chưa trả.']);
        $this->assertSame(2, $book->fresh()->available);

        // Phiếu đã quá hạn vẫn là phiếu chưa trả (BR-01)
        BorrowRecord::where('user_id', $reader->id)->update(['status' => 'overdue']);
        $this->actingAs($reader)->post(route('borrow.store', $book))
            ->assertSessionHasErrors(['book' => 'Bạn đang mượn cuốn sách này và chưa trả.']);
        $this->assertDatabaseCount('borrow_records', 1);
    }

    public function test_uc04_fourth_unreturned_loan_is_rejected(): void
    {
        $reader = $this->reader();
        foreach ([1, 2, 3] as $ignored) {
            $this->actingAs($reader)->post(route('borrow.store', $this->book()))->assertSessionHas('success');
        }
        $fourth = $this->book();

        $this->actingAs($reader)->post(route('borrow.store', $fourth))
            ->assertSessionHasErrors(['book' => 'Bạn chỉ được mượn tối đa 3 đầu sách cùng lúc.']);

        $this->assertSame(3, BorrowRecord::where('user_id', $reader->id)->count());
        $this->assertSame(2, $fourth->fresh()->available);
    }

    public function test_uc04_returned_book_does_not_count_toward_the_three_book_limit_or_duplicate_rule(): void
    {
        $reader = $this->reader();
        $book = $this->book();
        $this->record($reader, $book, ['status' => 'returned', 'return_date' => today()]);
        $this->record($reader, $this->book(), ['status' => 'returned', 'return_date' => today()]);
        $this->record($reader, $this->book(), ['status' => 'returned', 'return_date' => today()]);
        $this->record($reader, $this->book());

        $this->actingAs($reader)->post(route('borrow.store', $book))->assertSessionHas('success');

        $this->assertSame(1, $book->fresh()->available);
    }

    // -------------------------------------------------------------- UC06 – Trả sách

    public function test_uc06_on_time_return_has_no_fine_and_restores_stock(): void
    {
        Carbon::setTestNow('2026-09-01');
        $book = $this->book();
        $record = app(BorrowService::class)->borrow($this->reader(), $book);

        // Trả đúng ngày hạn cuối: chưa trễ
        Carbon::setTestNow('2026-09-15');
        app(BorrowService::class)->returnBook($record);

        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertSame('2026-09-15', $record->return_date->toDateString());
        $this->assertSame(0, $record->fine_amount);
        $this->assertTrue($record->is_paid);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc06_late_return_calculates_fine_and_leaves_it_unpaid(): void
    {
        Carbon::setTestNow('2026-09-01');
        $book = $this->book();
        $record = app(BorrowService::class)->borrow($this->reader(), $book);

        Carbon::setTestNow('2026-09-18'); // trễ 3 ngày
        app(BorrowService::class)->returnBook($record);

        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertSame(6000, $record->fine_amount);
        $this->assertFalse($record->is_paid);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc06_late_return_with_fine_collected_immediately_is_marked_paid(): void
    {
        $book = $this->book(['available' => 1]);
        $record = $this->record($this->reader(), $book, ['borrow_date' => today()->subDays(20), 'due_date' => today()->subDays(6), 'status' => 'overdue']);

        app(BorrowService::class)->returnBook($record, true);

        $record->refresh();
        $this->assertSame(12000, $record->fine_amount);
        $this->assertTrue($record->is_paid);
    }

    public function test_uc06_fine_rate_comes_from_config(): void
    {
        config(['library.fine_per_day' => 5000]);
        $book = $this->book(['available' => 1]);
        $record = $this->record($this->reader(), $book, ['borrow_date' => today()->subDays(16), 'due_date' => today()->subDays(2)]);

        app(BorrowService::class)->returnBook($record);

        $this->assertSame(10000, $record->fresh()->fine_amount);
    }

    public function test_uc06_admin_confirms_return_via_http(): void
    {
        $book = $this->book(['available' => 1]);
        $record = $this->record($this->reader(), $book);

        $this->actingAs($this->admin())->patch(route('borrow.return', $record))
            ->assertSessionHas('success', 'Đã xác nhận trả sách.');

        $this->assertSame('returned', $record->fresh()->status);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc06_return_cannot_be_repeated_and_does_not_increase_stock_twice(): void
    {
        $book = $this->book(['available' => 1]);
        $record = $this->record($this->reader(), $book);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('borrow.return', $record))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('borrow.return', $record))
            ->assertSessionHasErrors(['record' => 'Phiếu này đã được trả trước đó.']);

        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc06_return_is_not_blocked_by_overdue_hidden_book_or_locked_reader(): void
    {
        $book = $this->book(['is_active' => false, 'available' => 1]);
        $reader = $this->reader(['status' => 'locked']);
        $record = $this->record($reader, $book, ['borrow_date' => today()->subDays(20), 'due_date' => today()->subDays(6), 'status' => 'overdue']);

        $this->actingAs($this->admin())->patch(route('borrow.return', $record))->assertSessionHas('success');

        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertSame(12000, $record->fine_amount);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc06_invalid_stock_rolls_back_the_whole_return(): void
    {
        $book = $this->book(['total_quantity' => 2, 'available' => 2]); // dữ liệu bất thường (BR-13)
        $record = $this->record($this->reader(), $book, ['borrow_date' => today()->subDays(20), 'due_date' => today()->subDays(6)]);

        $this->assertRejected(
            fn () => app(BorrowService::class)->returnBook($record),
            'record',
            'Số lượng sách hiện tại không hợp lệ.'
        );

        $record->refresh();
        $this->assertSame('borrowing', $record->status);
        $this->assertNull($record->return_date);
        $this->assertSame(0, $record->fine_amount);
        $this->assertSame(2, $book->fresh()->available);
    }

    public function test_uc06_only_admin_can_confirm_return(): void
    {
        $record = $this->record($this->reader(), $this->book(['available' => 1]));

        $this->patch(route('borrow.return', $record))->assertRedirect(route('login'));
        $this->actingAs($this->reader())->patch(route('borrow.return', $record))->assertForbidden();

        $this->assertSame('borrowing', $record->fresh()->status);
    }

    public function test_status_lifecycle_borrowing_to_overdue_to_returned(): void
    {
        Carbon::setTestNow('2026-09-01');
        $book = $this->book();
        $record = app(BorrowService::class)->borrow($this->reader(), $book);

        Carbon::setTestNow('2026-09-20');
        $this->artisan('library:mark-overdue')->assertSuccessful();
        $this->assertSame('overdue', $record->fresh()->status);

        app(BorrowService::class)->returnBook($record);

        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertSame(10000, $record->fine_amount); // trễ 5 ngày
        $this->assertSame(2, $book->fresh()->available);
    }

    // ---------------------------------------------- UC09 – Quản lý phiếu mượn – trả

    public function test_uc09_only_admin_can_open_the_list(): void
    {
        $this->get(route('borrow.index'))->assertRedirect(route('login'));
        $this->actingAs($this->reader())->get(route('borrow.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('borrow.index'))->assertOk();
    }

    public function test_uc09_list_shows_user_code_and_book_code_instead_of_internal_ids(): void
    {
        $reader = $this->reader();
        $book = $this->book();
        $this->record($reader, $book);

        $this->actingAs($this->admin())->get(route('borrow.index'))
            ->assertOk()
            ->assertSee($reader->user_code)
            ->assertSee($reader->name)
            ->assertSee($book->book_code)
            ->assertSee($book->title)
            ->assertSee('Xác nhận trả');
    }

    public function test_uc09_list_is_sorted_newest_first_and_paginated_by_15(): void
    {
        $reader = $this->reader();
        foreach (range(1, 16) as $i) {
            $this->record($reader, $this->book(), ['borrow_date' => today()->subDays(30 - $i), 'due_date' => today()->subDays(16 - $i)]);
        }
        $admin = $this->admin();

        $page1 = $this->actingAs($admin)->get(route('borrow.index'))->assertOk();
        $records = $page1->viewData('records');
        $this->assertSame(16, $records->total());
        $this->assertCount(15, $records->items());
        $dates = $records->getCollection()->map(fn ($r) => $r->borrow_date->toDateString())->all();
        $newestFirst = $dates;
        rsort($newestFirst);
        $this->assertSame($newestFirst, $dates);

        $page2 = $this->actingAs($admin)->get(route('borrow.index', ['page' => 2]))->assertOk();
        $this->assertCount(1, $page2->viewData('records')->items());
    }

    public function test_uc09_filters_by_status_unpaid_fine_and_keyword(): void
    {
        $admin = $this->admin();
        $readerA = $this->reader();
        $readerB = $this->reader();
        $book1 = $this->book();
        $book2 = $this->book();
        $book3 = $this->book();
        $book4 = $this->book();
        $book5 = $this->book();

        $borrowing = $this->record($readerA, $book1);
        $overdue = $this->record($readerB, $book2, ['status' => 'overdue', 'due_date' => today()->subDay()]);
        $returnedClean = $this->record($readerB, $book3, ['status' => 'returned', 'return_date' => today()]);
        $returnedUnpaid = $this->record($readerB, $book4, ['status' => 'returned', 'return_date' => today(), 'fine_amount' => 4000, 'is_paid' => false]);
        $returnedPaid = $this->record($readerB, $book5, ['status' => 'returned', 'return_date' => today(), 'fine_amount' => 4000, 'is_paid' => true]);

        $get = fn (array $query) => $this->actingAs($admin)->get(route('borrow.index', $query))->assertOk();

        $this->assertSame([$overdue->id], $this->listedIds($get(['status' => 'overdue'])));
        $this->assertSame([$borrowing->id], $this->listedIds($get(['status' => 'borrowing'])));
        $this->assertSame(
            [$returnedClean->id, $returnedUnpaid->id, $returnedPaid->id],
            $this->listedIds($get(['status' => 'returned']))
        );
        $this->assertSame([$returnedUnpaid->id], $this->listedIds($get(['unpaid' => 1])));
        $this->assertSame([$returnedUnpaid->id], $this->listedIds($get(['status' => 'returned', 'unpaid' => 1])));
        $this->assertSame([], $this->listedIds($get(['status' => 'overdue', 'unpaid' => 1])));
        $this->assertSame([$borrowing->id], $this->listedIds($get(['q' => $readerA->user_code])));
        $this->assertSame([$returnedClean->id], $this->listedIds($get(['q' => $book3->book_code])));
    }

    public function test_uc09_invalid_status_filter_is_ignored(): void
    {
        $admin = $this->admin();
        $reader = $this->reader();
        $a = $this->record($reader, $this->book());
        $b = $this->record($reader, $this->book(), ['status' => 'overdue']);

        $response = $this->actingAs($admin)->get(route('borrow.index', ['status' => 'khong-hop-le']))->assertOk();

        $this->assertSame([$a->id, $b->id], $this->listedIds($response));
    }

    public function test_uc09_shows_message_when_no_record_matches(): void
    {
        $this->record($this->reader(), $this->book());

        $this->actingAs($this->admin())->get(route('borrow.index', ['q' => 'ZZZ-KHONG-CO']))
            ->assertOk()
            ->assertSee('Không có phiếu phù hợp.');
    }

    public function test_uc09_unpaid_fine_shows_label_and_pay_button_until_collected(): void
    {
        $admin = $this->admin();
        $record = $this->record($this->reader(), $this->book(), ['status' => 'returned', 'return_date' => today(), 'fine_amount' => 4000, 'is_paid' => false]);

        // 'text-bg-warning' chỉ xuất hiện ở nhãn "Chưa thu" (ô lọc cũng có chữ "Chưa thu phạt" nên không dùng chữ để kiểm tra)
        $this->actingAs($admin)->get(route('borrow.index'))
            ->assertSee('text-bg-warning', false)
            ->assertSee('Xác nhận thu phạt');

        $this->actingAs($admin)->patch(route('borrow.pay', $record))
            ->assertSessionHas('success', 'Đã xác nhận thu tiền phạt.');
        $this->assertTrue($record->fresh()->is_paid);

        $this->actingAs($admin)->get(route('borrow.index'))
            ->assertSee('Đã thu')
            ->assertDontSee('text-bg-warning', false)
            ->assertDontSee('Xác nhận thu phạt');
    }

    public function test_uc09_cannot_collect_fine_when_there_is_nothing_to_collect(): void
    {
        $admin = $this->admin();
        $reader = $this->reader();
        $noFine = $this->record($reader, $this->book(), ['status' => 'returned', 'return_date' => today()]);
        $alreadyPaid = $this->record($reader, $this->book(), ['status' => 'returned', 'return_date' => today(), 'fine_amount' => 4000, 'is_paid' => true]);
        $notReturned = $this->record($reader, $this->book(), ['status' => 'overdue', 'fine_amount' => 0]);

        foreach ([$noFine, $alreadyPaid, $notReturned] as $record) {
            $this->actingAs($admin)->patch(route('borrow.pay', $record))
                ->assertSessionHasErrors(['record' => 'Phiếu không có tiền phạt cần thu.']);
        }

        $this->assertSame(0, $noFine->fresh()->fine_amount);
        $this->assertSame('overdue', $notReturned->fresh()->status);
    }

    public function test_uc09_only_admin_can_collect_fine(): void
    {
        $record = $this->record($this->reader(), $this->book(), ['status' => 'returned', 'return_date' => today(), 'fine_amount' => 4000, 'is_paid' => false]);

        $this->patch(route('borrow.pay', $record))->assertRedirect(route('login'));
        $this->actingAs($this->reader())->patch(route('borrow.pay', $record))->assertForbidden();

        $this->assertFalse($record->fresh()->is_paid);
    }
}
