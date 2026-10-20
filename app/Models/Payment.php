<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Payment extends Model {
 protected $fillable = ['user_id', 'borrow_record_id', 'membership_card_id', 'type', 'amount', 'method', 'status', 'paid_at', 'received_by', 'reference'];
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function borrowRecord(): BelongsTo { return $this->belongsTo(BorrowRecord::class); }
 public function membershipCard(): BelongsTo { return $this->belongsTo(MembershipCard::class); }
 public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}
