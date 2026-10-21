<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DepositTransaction extends Model {
 protected $fillable = ['borrow_record_id', 'type', 'adjusts_id', 'amount', 'reason', 'processed_by', 'processed_at'];
 public function borrowRecord(): BelongsTo { return $this->belongsTo(BorrowRecord::class); }
 public function adjustedEntry(): BelongsTo { return $this->belongsTo(DepositTransaction::class, 'adjusts_id'); }
 public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
