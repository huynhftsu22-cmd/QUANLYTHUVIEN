<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_code', 'name', 'email', 'phone', 'address', 'password', 'role', 'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function membershipCards(): HasMany { return $this->hasMany(MembershipCard::class); }
    public function studentVerifications(): HasMany { return $this->hasMany(StudentVerification::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    /**
     * Thẻ hợp lệ cho một phiếu (BR-14): status = active và expires_at >= $dueDate
     * (thẻ phải phủ hết thời gian mượn). Trả MembershipCard hoặc null.
     */
    public function validCardFor(\DateTimeInterface|string $dueDate): ?MembershipCard
    {
        return $this->membershipCards()
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', $dueDate)
            ->orderByDesc('expires_at')
            ->first();
    }

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowRecord::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Chuẩn hóa SĐT về dạng 0xxxxxxxxx để chặn trùng số dù nhập +84901234567 hay 0901234567.
     * Chuỗi không đúng định dạng được giữ nguyên để validate báo lỗi như bình thường.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        return preg_match('/^\+84([35789][0-9]{8})$/', $phone, $m) ? '0'.$m[1] : $phone;
    }

    public static function nextCode(string $role): string
    {
        $prefix = $role === 'admin' ? 'NV' : 'DG';
        $last = static::where('user_code', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('user_code')->value('user_code');

        return $prefix.str_pad((string) (((int) substr((string) $last, 2)) + 1), 4, '0', STR_PAD_LEFT);
    }
}
