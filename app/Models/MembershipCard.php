<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class MembershipCard extends Model {
 protected $fillable = ['user_id', 'card_code', 'months', 'fee_amount', 'issued_at', 'expires_at', 'status', 'issued_by'];
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
 public function payments(): HasMany { return $this->hasMany(Payment::class); }
 public function borrowRecords(): HasMany { return $this->hasMany(BorrowRecord::class); }
}
