<?php

use App\Models\BorrowRecord;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('library:mark-overdue', function () {
    $count = BorrowRecord::where('status', 'borrowing')
        ->whereDate('due_date', '<', today())
        ->update(['status' => 'overdue']);
    $this->info("Đã cập nhật {$count} phiếu quá hạn.");
})->purpose('Chuyển các phiếu mượn quá hạn sang trạng thái overdue');

Schedule::command('library:mark-overdue')->daily();

// Hủy giữ chỗ quá 24 giờ (theo hold_hours của chính sách gắn với phiếu).
Artisan::command('library:expire-reservations', function () {
    $count = 0;
    BorrowRecord::with('rentalPolicy')->where('status', 'pending')->chunkById(100, function ($records) use (&$count) {
        foreach ($records as $item) {
            $hours = \App\Models\RentalPolicy::active()->hold_hours;
            if (\Carbon\Carbon::parse($item->requested_at)->addHours($hours)->isPast()) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($item, &$count) {
                    $record = BorrowRecord::whereKey($item->id)->lockForUpdate()->first();
                    if ($record && $record->status === 'pending') {
                        $record->update(['status' => 'cancelled', 'cancel_reason' => 'Hết hạn giữ sách']);
                        $record->bookCopy()->where('status', 'reserved')->update(['status' => 'available']);
                        $count++;
                    }
                });
            }
        }
    });
    $this->info("Đã hủy {$count} phiếu giữ chỗ quá hạn.");
})->purpose('Tự hủy các phiếu giữ chỗ hết hạn');

Schedule::command('library:expire-reservations')->hourly();

Artisan::command('library:sync-catalog', function () {
    $this->call('db:seed', ['--class' => \Database\Seeders\SaySachCatalogSeeder::class]);
    $this->info('Đã đồng bộ thông tin ấn bản. Bản sao sách và phiếu mượn được giữ nguyên.');
})->purpose('Cập nhật danh mục Say Sách theo ấn bản đã đối chiếu');
