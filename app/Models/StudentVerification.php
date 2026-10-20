<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class StudentVerification extends Model {
 protected $fillable = ['user_id', 'learner_type', 'school_name', 'student_code', 'evidence_path', 'status', 'verified_by', 'verified_at', 'expires_at', 'reject_reason'];
 public function getLearnerTypeLabelAttribute(): string { return ['school'=>'Học sinh','university'=>'Sinh viên'][$this->learner_type ?? 'university'] ?? 'Chưa xác định'; }
 public function getStatusLabelAttribute(): string {
  if ($this->status === 'approved' && $this->expires_at && \Carbon\Carbon::parse($this->expires_at)->lt(today())) return 'Hết hạn';
  return ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Đã từ chối'][$this->status] ?? $this->status;
 }
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 /** Xác minh HS-SV approved còn hạn của độc giả (BR-22), hoặc null. Hết hạn tính theo expires_at. */
 public static function activeFor(User|int $user): ?self
 {
  return static::where('user_id', $user instanceof User ? $user->id : $user)
   ->where('status', 'approved')
   ->whereDate('expires_at', '>=', today())
   ->orderByDesc('expires_at')
   ->first();
 }
 public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
