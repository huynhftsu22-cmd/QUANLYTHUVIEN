<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BorrowRecord extends Model {
 protected $fillable = ['user_id', 'book_copy_id', 'membership_card_id', 'rental_policy_id', 'borrow_date', 'due_date', 'return_date', 'rental_days', 'requested_at', 'rental_fee', 'discount_amount', 'deposit_amount', 'cover_price', 'fine_amount', 'damage_fee', 'return_condition', 'cancel_reason', 'damage_note', 'lent_by', 'status'];
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function bookCopy(): BelongsTo { return $this->belongsTo(BookCopy::class); }
 public function membershipCard(): BelongsTo { return $this->belongsTo(MembershipCard::class); }
 public function rentalPolicy(): BelongsTo { return $this->belongsTo(RentalPolicy::class); }
 public function lender(): BelongsTo { return $this->belongsTo(User::class, 'lent_by'); }
 public function payments(): HasMany { return $this->hasMany(Payment::class); }
 public function depositTransactions(): HasMany { return $this->hasMany(DepositTransaction::class); }

 public function getDepositSummaryAttribute(): array {
  $entries = $this->exists ? $this->depositTransactions : collect();
  $held = $entries->isEmpty() ? (int) $this->deposit_amount : (int) $entries->where('type','hold')->sum('amount');
  $deducted = (int) $entries->where('type','deduct')->sum('amount');
  $refunded = (int) $entries->where('type','refund')->sum('amount');
  $byId = $entries->keyBy('id');
  foreach ($entries as $entry) {
   if ($entry->type === 'adjust_down') $held -= (int) $entry->amount;
   if ($entry->type === 'adjust_up') {
    $original = $byId->get($entry->adjusts_id);
    if ($original?->type === 'refund') $refunded -= (int) $entry->amount;
    if ($original?->type === 'deduct') $deducted -= (int) $entry->amount;
   }
  }
  return ['held'=>$held, 'deducted'=>$deducted, 'refunded'=>$refunded, 'balance'=>$held-$deducted-$refunded];
 }
}
