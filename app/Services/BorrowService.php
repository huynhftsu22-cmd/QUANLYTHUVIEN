<?php
namespace App\Services;
use App\Models\BorrowRecord;
use Illuminate\Support\Facades\DB;
class BorrowService {
 public function cancelPending(BorrowRecord $item,string $reason): void {
  DB::transaction(function()use($item,$reason){
   $record=BorrowRecord::whereKey($item->id)->lockForUpdate()->firstOrFail();
   if($record->status!=='pending') abort(422,'Phiếu không còn ở trạng thái chờ');
   $copy=$record->bookCopy()->lockForUpdate()->firstOrFail();
   $record->update(['status'=>'cancelled','cancel_reason'=>$reason]);
   if($copy->status==='reserved')$copy->update(['status'=>'available']);
  });
 }
}
