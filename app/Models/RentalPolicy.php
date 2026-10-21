<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class RentalPolicy extends Model {
 protected $fillable = ['daily_rate', 'student_discount', 'deposit_rate', 'member_deposit_rate', 'late_fee', 'damage_rate', 'lost_rate', 'lost_admin_fee', 'card_fee_monthly', 'max_days', 'max_books', 'hold_hours', 'effective_at', 'is_active'];
 public function borrowRecords(): HasMany { return $this->hasMany(BorrowRecord::class); }
 /** Chính sách đang áp dụng (is_active = 1). Báo lỗi cấu hình nếu chưa có dòng nào. */
 public static function active(): self
 {
  $policy = static::where('is_active', true)->orderByDesc('effective_at')->orderByDesc('id')->first();
  if (! $policy) {
   throw new \RuntimeException('Chưa có chính sách giá đang áp dụng (rental_policies.is_active = 1). Chạy php artisan db:seed.');
  }
  return $policy;
 }
}
