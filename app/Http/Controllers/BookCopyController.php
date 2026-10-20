<?php
namespace App\Http\Controllers;
use App\Models\{Book,BookCopy};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class BookCopyController extends Controller {
 public function index(Book $book){$copies=$book->copies()->orderBy('copy_code')->get();return view('copies.index',compact('book','copies'));}
 /** UC13: thêm 1–50 bản sao; copy_code tự sinh dạng <book_code>-01, -02... */
 public function store(Request $r,Book $book){
  $v=$r->validate(['quantity'=>'required|integer|between:1,50','shelf_location'=>'nullable|string|max:50']);
  DB::transaction(function()use($book,$v){
   $book=Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
   $max=0;$prefix=$book->book_code.'-';
   foreach(BookCopy::where('book_id',$book->id)->pluck('copy_code') as $code){
    if(preg_match('/^'.preg_quote($prefix,'/').'(\d+)$/',$code,$m))$max=max($max,(int)$m[1]);
   }
   for($i=1;$i<=$v['quantity'];$i++){
    $book->copies()->create(['copy_code'=>$prefix.str_pad((string)($max+$i),2,'0',STR_PAD_LEFT),'status'=>'available','shelf_location'=>$v['shelf_location']??null]);
   }
  });return back()->with('success','Đã tạo '.$v['quantity'].' bản sao');
 }
 public function update(Request $r,BookCopy $copy){$v=$r->validate(['status'=>'required|in:available,damaged,maintenance,lost,retired','condition_note'=>'nullable|max:255','shelf_location'=>'nullable|max:50']);if(in_array($copy->status,['borrowed','reserved']))return back()->with('error','Bản sao đang có giao dịch, không thể đổi trạng thái thủ công');$allowed=['available'=>['damaged','maintenance','lost','retired'],'damaged'=>['maintenance','retired'],'maintenance'=>['available','retired'],'lost'=>['available','retired'],'retired'=>[]];if($v['status']!==$copy->status && !in_array($v['status'],$allowed[$copy->status]??[],true))abort(422,'Không thể chuyển sang trạng thái này.');if($v['status']!==$copy->status && blank($v['condition_note']??null))return back()->with('error','Vui lòng nhập ghi chú tình trạng khi đổi trạng thái.');$copy->update($v);return back()->with('success','Đã cập nhật bản sao');}
}
