<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BookCopy extends Model {
 protected $fillable = ['book_id', 'copy_code', 'status', 'condition_note', 'shelf_location'];
 public function book(): BelongsTo { return $this->belongsTo(Book::class); }
 public function borrowRecords(): HasMany { return $this->hasMany(BorrowRecord::class); }
}
