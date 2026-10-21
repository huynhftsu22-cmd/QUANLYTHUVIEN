<?php
namespace App\Services;

use App\Models\{BorrowRecord, RentalPolicy, StudentVerification, User};
use Carbon\Carbon;

/** UC16: mọi phép tính dùng số nguyên đồng, làm tròn half-up. */
class PricingService
{
    public function quote(User $user,int $coverPrice,int $days,?RentalPolicy $policy=null,?Carbon $start=null): array {
        return $this->rental($user,$coverPrice,$days,$policy,$start);
    }
    public function settle(BorrowRecord $record,string $condition,?Carbon $returnDate=null,?int $damageOverride=null): array {
        return $this->returns($record,$condition,$returnDate,$damageOverride);
    }
    public function rental(User $user, int $coverPrice, int $days, ?RentalPolicy $policy = null, ?Carbon $start = null): array
    {
        $policy ??= RentalPolicy::active();
        $start ??= today();
        if ($days < 1 || $days > $policy->max_days) throw new \InvalidArgumentException('Số ngày mượn không hợp lệ');
        $due = $start->copy()->addDays($days);
        $card = $user->validCardFor($due);
        $base = $card ? 0 : $policy->daily_rate * $days;
        $discount = !$card && StudentVerification::activeFor($user)
            ? (int) round($base * $policy->student_discount / 100, 0, PHP_ROUND_HALF_UP) : 0;
        // Membership waives rental fees only; every reader pays the standard deposit.
        $rate = (int) $policy->deposit_rate;
        $deposit = $rate === 100 ? $coverPrice : (int) (ceil($coverPrice * $rate / 100 / 1000) * 1000);
        return [
            'due_date' => $due->toDateString(), 'membership_card_id' => $card?->id,
            'rental_fee' => $base - $discount, 'discount_amount' => $discount,
            'deposit_amount' => $deposit,
            'cover_price' => $coverPrice,
        ];
    }

    public function returns(BorrowRecord $record, string $condition, ?Carbon $returnDate = null, ?int $damageOverride = null): array
    {
        $returnDate ??= today();
        $policy = $record->rentalPolicy;
        if (!$policy || $record->cover_price === null || !$record->due_date) {
            throw new \RuntimeException('Phiếu thiếu chính sách hoặc dữ liệu chốt lúc giao sách');
        }
        $lateDays = max(0, (int) Carbon::parse($record->due_date)->startOfDay()->diffInDays($returnDate->copy()->startOfDay(), false));
        $late = min((int) $record->cover_price, $lateDays * $policy->late_fee);
        $damage = match ($condition) {
            'good' => 0,
            'damaged' => $damageOverride ?? (int) round($record->cover_price * $policy->damage_rate / 100, 0, PHP_ROUND_HALF_UP),
            'lost' => (int) round($record->cover_price * $policy->lost_rate / 100, 0, PHP_ROUND_HALF_UP) + $policy->lost_admin_fee,
            default => throw new \InvalidArgumentException('Tình trạng sách không hợp lệ'),
        };
        if ($damage < 0 || ($condition === 'damaged' && $damage > $record->cover_price)) {
            throw new \InvalidArgumentException('Mức bồi thường không hợp lệ');
        }
        $total = $late + $damage;
        $depositHeld = max(0, $record->deposit_summary['balance']);
        $deduct = min($depositHeld, $total);
        return ['late_days' => $lateDays, 'late_fee' => $late, 'damage_fee' => $damage,
            'deduct' => $deduct, 'refund' => $depositHeld - $deduct, 'deposit_held' => $depositHeld,
            'extra_payment' => $total - $deduct];
    }
}
