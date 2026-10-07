<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Toàn bộ nghiệp vụ mượn – trả (UC04, UC06, UC09).
 * Mỗi thao tác chạy trong một transaction và khóa dòng (lockForUpdate)
 * để dữ liệu phiếu và số lượng sách luôn nhất quán.
 */
class BorrowService
{
    /**
     * UC04 – Mượn sách. Kiểm tra lần lượt BR-09, BR-02, BR-01, BR-04;
     * tạo phiếu 'borrowing' (BR-03) và giảm available (BR-05) trong cùng transaction.
     */
    public function borrow(User $user, Book $book): BorrowRecord
    {
        return DB::transaction(function () use ($user, $book) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $book = Book::lockForUpdate()->findOrFail($book->id);

            if (! $user->isActive()) { // BR-09
                $this->reject('book', 'Tài khoản không hoạt động nên không thể mượn sách.');
            }
            if ($user->role !== 'user') {
                $this->reject('book', 'Chỉ độc giả được mượn sách.');
            }
            if (! $book->is_active || $book->available < 1) { // BR-02
                $this->reject('book', 'Sách đã hết hoặc đang bị ẩn.');
            }

            $unreturned = $user->borrowRecords()->unreturned();
            if ((clone $unreturned)->where('book_id', $book->id)->exists()) { // BR-01
                $this->reject('book', 'Bạn đang mượn cuốn sách này và chưa trả.');
            }
            $maxLoans = (int) config('library.max_active_loans');
            if ((clone $unreturned)->count() >= $maxLoans) { // BR-04
                $this->reject('book', "Bạn chỉ được mượn tối đa {$maxLoans} đầu sách cùng lúc.");
            }

            $today = today();
            $record = BorrowRecord::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'borrow_date' => $today,
                'due_date' => $today->copy()->addDays((int) config('library.loan_days')), // BR-03
                'status' => BorrowRecord::STATUS_BORROWING,
                'fine_amount' => 0,
                'is_paid' => true,
            ]);
            $book->decrement('available'); // BR-05

            return $record;
        }, 3);
    }

    /**
     * UC06 – Trả sách. Ghi return_date, tính phạt (BR-07), cộng lại available (BR-06).
     * Không bị chặn bởi BR-09/BR-10: phiếu overdue, sách đã ẩn, tài khoản đã khóa vẫn được trả.
     */
    public function returnBook(BorrowRecord $record, bool $finePaid = false): BorrowRecord
    {
        return DB::transaction(function () use ($record, $finePaid) {
            $record = BorrowRecord::lockForUpdate()->findOrFail($record->id);
            if ($record->isReturned()) {
                $this->reject('record', 'Phiếu này đã được trả trước đó.');
            }
            $book = Book::lockForUpdate()->findOrFail($record->book_id);

            $returnDate = today();
            $fine = $this->lateDays($record, $returnDate) * (int) config('library.fine_per_day');

            $record->update([
                'return_date' => $returnDate,
                'status' => BorrowRecord::STATUS_RETURNED,
                'fine_amount' => $fine,
                'is_paid' => $fine === 0 || $finePaid, // is_paid = 0 chỉ khi có phạt mà chưa thu
            ]);

            if ($book->available >= $book->total_quantity) { // BR-13
                $this->reject('record', 'Số lượng sách hiện tại không hợp lệ.');
            }
            $book->increment('available');

            return $record->refresh();
        }, 3);
    }

    /** UC09 – Xác nhận thu phạt cho phiếu đã trả còn phạt chưa thu (BR-07). */
    public function markFinePaid(BorrowRecord $record): BorrowRecord
    {
        return DB::transaction(function () use ($record) {
            $record = BorrowRecord::lockForUpdate()->findOrFail($record->id);
            if (! $record->isReturned() || ! $record->hasUnpaidFine()) {
                $this->reject('record', 'Phiếu không có tiền phạt cần thu.');
            }
            $record->update(['is_paid' => true]);

            return $record;
        }, 3);
    }

    /** Số ngày trễ = max(0, ngày trả − hạn trả), luôn là số nguyên. */
    private function lateDays(BorrowRecord $record, CarbonInterface $returnDate): int
    {
        $due = $record->due_date->copy()->startOfDay();

        return max(0, (int) $due->diffInDays($returnDate->copy()->startOfDay(), false));
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
