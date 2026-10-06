<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowRecord extends Model
{
    use HasFactory;

    /** Giá trị status chốt cứng: viết thường, không thừa khoảng trắng. */
    public const STATUS_BORROWING = 'borrowing';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUSES = [self::STATUS_BORROWING, self::STATUS_RETURNED, self::STATUS_OVERDUE];

    public const STATUS_LABELS = [
        self::STATUS_BORROWING => 'Đang mượn',
        self::STATUS_OVERDUE => 'Quá hạn',
        self::STATUS_RETURNED => 'Đã trả',
    ];

    protected $fillable = ['user_id', 'book_id', 'borrow_date', 'due_date', 'return_date', 'status', 'fine_amount', 'is_paid'];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'fine_amount' => 'integer',
            'is_paid' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /** Phiếu chưa trả: borrowing hoặc overdue (dùng cho BR-01, BR-04). */
    public function scopeUnreturned(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_BORROWING, self::STATUS_OVERDUE]);
    }

    /** Phiếu có phạt chưa thu: fine_amount > 0 và is_paid = 0 (BR-07). */
    public function scopeUnpaidFine(Builder $query): Builder
    {
        return $query->where('fine_amount', '>', 0)->where('is_paid', false);
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function hasUnpaidFine(): bool
    {
        return $this->fine_amount > 0 && ! $this->is_paid;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
