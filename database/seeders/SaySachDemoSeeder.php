<?php

namespace Database\Seeders;

use App\Models\{Book, BookCopy, BorrowRecord, DepositTransaction, MembershipCard, MembershipRequest, Payment, RentalPolicy, StudentVerification, User};
use App\Services\PricingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Dữ liệu GIẢ LẬP phục vụ trình bày. Chỉ chạy trên database demo riêng.
 * Idempotent: không xóa dữ liệu, không cập nhật giao dịch đã có.
 */
class SaySachDemoSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection()->getDatabaseName();
        // Chỉ chạy khi người vận hành bật quyền nạp demo cho database Say Sách được chỉ định.
        if (!in_array($db, ['qlthuvien_saysach', 'qlthuvien_saysach_demo'], true)
            || getenv('SAYSACH_DEMO_SEED') !== 'YES') {
            throw new RuntimeException("Từ chối seed demo vào {$db}. Cần đúng database Say Sách và SAYSACH_DEMO_SEED=YES cho lần chạy này.");
        }
        DB::transaction(function () use ($db) {
            $admin = User::where('email', 'admin@example.com')->where('role', 'admin')->firstOrFail();
            $policy = RentalPolicy::active();
            $readers = [];
            foreach ([
                ['DG-DEMO-01', 'Nguyễn Minh Anh', 'minhanh.demo@example.test', '0908000101'],
                ['DG-DEMO-02', 'Trần Gia Huy', 'giahuy.demo@example.test', '0908000102'],
                ['DG-DEMO-03', 'Lê Thu Hà', 'thuha.demo@example.test', '0908000103'],
                ['DG-DEMO-04', 'Phạm Quốc Bảo', 'quocbao.demo@example.test', '0908000104'],
                ['DG-DEMO-05', 'Võ Ngọc Linh', 'ngoclinh.demo@example.test', '0908000105'],
            ] as [$code, $name, $email, $phone]) {
                $readers[] = User::firstOrCreate(['email' => $email], [
                    'user_code' => $code, 'name' => $name, 'phone' => $phone,
                    'address' => 'Phường Bến Thành', 'password' => 'Demo@12345',
                    'role' => 'user', 'status' => 'active',
                ]);
            }

            // Hai bản sao mỗi đầu sách và 1 bản sao thứ ba cho 5 đầu sách đầu tiên.
            $books = Book::where('is_active', true)->orderBy('id')->get();
            if ($books->count() < 12) throw new RuntimeException('Cần nạp danh mục Say Sách trước: php artisan db:seed --class=DatabaseSeeder');
            $copies = [];
            foreach ($books as $i => $book) {
                $number = $i < 5 ? 3 : 2;
                for ($n = 1; $n <= $number; $n++) {
                    $code = sprintf('DEMO-%03d-%02d', $i + 1, $n);
                    $copy = BookCopy::firstOrCreate(['copy_code' => $code], [
                        'book_id' => $book->id, 'status' => 'available',
                        'shelf_location' => sprintf('K%02d-T%02d', intdiv($i, 5) + 1, $n),
                        'condition_note' => 'Dữ liệu mô phỏng dùng để trình bày',
                    ]);
                    if ($copy->book_id !== $book->id) throw new RuntimeException("Mã bản sao {$code} đã được sử dụng cho sách khác.");
                    $copies[$i][$n] = $copy;
                }
            }

            $card = MembershipCard::firstOrCreate(['card_code' => 'TV-DEMO-01'], [
                'user_id' => $readers[0]->id, 'months' => 6,
                'fee_amount' => $policy->card_fee_monthly * 6,
                'issued_at' => today()->subMonth()->toDateString(),
                'expires_at' => today()->addMonths(5)->toDateString(),
                'status' => 'active', 'issued_by' => $admin->id,
            ]);
            if ($card->user_id !== $readers[0]->id) throw new RuntimeException('Mã thẻ demo bị trùng.');
            MembershipRequest::firstOrCreate(['user_id' => $readers[0]->id, 'membership_card_id' => $card->id], [
                'months' => 6, 'fee_amount' => $card->fee_amount,
                'status' => 'approved', 'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subMonth(),
            ]);
            $this->payment($readers[0], $admin, 'card_fee', (int)$card->fee_amount, null, $card, now()->subMonth());
            MembershipRequest::firstOrCreate(['user_id' => $readers[4]->id, 'status' => 'pending'], [
                'months' => 3, 'fee_amount' => $policy->card_fee_monthly * 3,
            ]);

            // Các hồ sơ ví dụ không đính kèm giấy tờ thật; chỉ phục vụ hiển thị trạng thái.
            foreach ([
                [1, 'university', 'Trường Đại học Demo', 'SV-DEMO-02', 'approved'],
                [2, 'school', 'Trường THPT Demo', 'HS-DEMO-03', 'pending'],
                [3, 'university', 'Trường Đại học Demo', 'SV-DEMO-04', 'rejected'],
            ] as [$i, $type, $school, $studentCode, $status]) {
                StudentVerification::firstOrCreate(['user_id' => $readers[$i]->id, 'student_code' => $studentCode], [
                    'learner_type' => $type, 'school_name' => $school,
                    'evidence_path' => null, 'status' => $status,
                    'verified_by' => $status === 'pending' ? null : $admin->id,
                    'verified_at' => $status === 'pending' ? null : today()->subDays(10)->toDateString(),
                    'expires_at' => $status === 'approved' ? today()->addMonths(8)->toDateString() : null,
                    'reject_reason' => $status === 'rejected' ? 'Hồ sơ minh họa chưa đủ minh chứng' : null,
                ]);
            }

            // Mỗi phiếu có bản sao riêng. Không chỉnh sửa phiếu hoặc bản sao đã phát sinh giao dịch.
            $scenarios = [
                [0,1,0,'pending', 7, 0], [1,1,1,'pending', 10, 0],
                [2,2,0,'borrowing', 10, -2], [3,3,0,'borrowing', 14, -3],
                [4,4,0,'overdue', 7, -12], [1,5,0,'overdue', 5, -10],
                [0,6,0,'returned', 7, -13], [2,7,0,'returned', 10, -17],
                [3,8,0,'returned', 5, -8], [4,9,0,'returned', 7, -18],
                [1,10,0,'cancelled', 7, -3],
            ];
            foreach ($scenarios as [$userIndex, $bookIndex, $copyOffset, $status, $days, $startOffset]) {
                $user = $readers[$userIndex];
                $copy = $copies[$bookIndex][$copyOffset + 1];
                // Giữ nguyên dữ liệu nếu người dùng đã thao tác trên bản sao này.
                if (BorrowRecord::where('book_copy_id', $copy->id)->exists()) continue;
                if ($copy->status !== 'available') continue;
                $start = today()->addDays($startOffset);
                $requested = $status === 'pending' ? now()->subMinutes(30) : $start->copy()->setTime(9, 0);
                $recordData = [
                    'user_id' => $user->id, 'book_copy_id' => $copy->id,
                    'rental_days' => $days, 'requested_at' => $requested,
                    'status' => $status,
                ];
                if ($status === 'pending') {
                    $record = BorrowRecord::create($recordData);
                    $copy->update(['status' => 'reserved']);
                    continue;
                }
                if ($status === 'cancelled') {
                    BorrowRecord::create($recordData + ['cancel_reason' => 'Hủy đặt giữ (dữ liệu minh họa)']);
                    continue;
                }
                $quote = app(PricingService::class)->rental($user, (int)$copy->book->cover_price, $days, $policy, $start->copy());
                $recordData = array_merge($recordData, $quote, [
                    'rental_policy_id' => $policy->id,
                    'borrow_date' => $start->toDateString(), 'lent_by' => $admin->id,
                ]);
                if ($status === 'returned') {
                    // Một ví dụ phí trễ hạn; các phiếu khác trả đúng hạn.
                    $returnedAt = $bookIndex === 7 ? $start->copy()->addDays($days + 2) : $start->copy()->addDays($days - 1);
                    $lateDays = max(0, (int)$start->copy()->addDays($days)->diffInDays($returnedAt, false));
                    $fine = min((int)$quote['cover_price'], $lateDays * (int)$policy->late_fee);
                    $recordData = array_merge($recordData, [
                        'return_date' => $returnedAt->toDateString(),
                        'return_condition' => 'good', 'fine_amount' => $fine,
                    ]);
                }
                $record = BorrowRecord::create($recordData);
                $copy->update(['status' => $status === 'returned' ? 'available' : 'borrowed']);
                if ($quote['rental_fee'] > 0) {
                    $this->payment($user, $admin, 'rental_fee', (int)$quote['rental_fee'], $record, null, $start->copy());
                }
                $deposit = (int)$quote['deposit_amount'];
                if ($deposit > 0) {
                    $this->deposit($record, $admin, 'hold', $deposit, $start->copy());
                    if ($status === 'returned') {
                        $fine = (int)($recordData['fine_amount'] ?? 0);
                        $deduct = min($deposit, $fine);
                        if ($deduct > 0) $this->deposit($record, $admin, 'deduct', $deduct, $returnedAt->copy());
                        if ($deposit > $deduct) $this->deposit($record, $admin, 'refund', $deposit - $deduct, $returnedAt->copy());
                        if ($fine > $deduct) $this->payment($user, $admin, 'late_fine', $fine - $deduct, $record, null, $returnedAt->copy());
                    }
                }
            }
            $this->command?->info("Đã đồng bộ dữ liệu giả lập vào {$db}; chạy lại sẽ không tạo thêm các phiếu trên cùng bản sao.");
        });
    }

    private function payment(User $user, User $admin, string $type, int $amount, ?BorrowRecord $record, ?MembershipCard $card, $paidAt): void
    {
        if ($amount < 1) return;
        $ref = sprintf('SAYSACH-DEMO-%s-%s', $type, $record?->id ?? ('CARD-'.$card?->id));
        Payment::firstOrCreate(['reference' => $ref], [
            'user_id' => $user->id, 'borrow_record_id' => $record?->id,
            'membership_card_id' => $card?->id, 'type' => $type,
            'amount' => $amount, 'method' => 'cash', 'status' => 'paid',
            'paid_at' => $paidAt, 'received_by' => $admin->id,
        ]);
    }

    private function deposit(BorrowRecord $record, User $admin, string $type, int $amount, $at): void
    {
        if ($amount < 1) return;
        DepositTransaction::firstOrCreate(['borrow_record_id' => $record->id, 'type' => $type], [
            'amount' => $amount, 'reason' => 'Dữ liệu giả lập trình bày',
            'processed_by' => $admin->id, 'processed_at' => $at,
        ]);
    }
}
