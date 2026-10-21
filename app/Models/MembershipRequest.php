<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MembershipRequest extends Model
{
    protected $fillable = ['user_id','months','fee_amount','status','membership_card_id','reviewed_by','reviewed_at'];
    protected function casts(): array { return ['months'=>'integer','fee_amount'=>'integer','reviewed_at'=>'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function card(): BelongsTo { return $this->belongsTo(MembershipCard::class, 'membership_card_id'); }
}
