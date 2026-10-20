<?php
namespace App\Http\Controllers;
use App\Models\{Book,Author,Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\PricingService;
class BookController extends Controller {
 public function index(Request $r) { $books=Book::with(['author','category','copies'])->where('is_active',true)->when($r->q,fn($q)=>$q->where(function($query)use($r){$query->where('title','like','%'.$r->q.'%')->orWhere('book_code','like','%'.$r->q.'%')->orWhereHas('author',fn($a)=>$a->where('name','like','%'.$r->q.'%'));}))->when($r->author_id,fn($q)=>$q->where('author_id',$r->author_id))->when($r->category_id,fn($q)=>$q->where('category_id',$r->category_id))->latest()->paginate(12); return view('books.index',['books'=>$books,'authors'=>Author::orderBy('name')->get(),'categories'=>Category::orderBy('name')->get()]); }
 public function show(Request $request, Book $book, PricingService $pricing) {
  if (!$book->is_active && (!auth()->check() || auth()->user()->role !== 'admin')) abort(404);
  $book->load(['author','category','copies']);
  $policy = \App\Models\RentalPolicy::active();
  $maxDays = min(30, (int) $policy->max_days);
  $days = max(1, min($maxDays, (int) old('rental_days', $request->query('rental_days', $request->query('days', 14)))));
  $estimate = null;
  if (auth()->check() && auth()->user()->role === 'user') $estimate = $pricing->rental(auth()->user(), (int) $book->cover_price, $days, $policy);
  return view('books.show', compact('book','estimate','days','maxDays','policy'));
 }
 public function quote(Request $request, Book $book, PricingService $pricing) {
  abort_unless($book->is_active, 404);
  $policy = \App\Models\RentalPolicy::active();
  $maxDays = min(30, (int) $policy->max_days);
  $data = $request->validate(['days' => 'required|integer|between:1,'.$maxDays]);
  $quote = $pricing->rental($request->user(), (int) $book->cover_price, (int) $data['days'], $policy);
  return response()->json($quote + ['days' => (int) $data['days'], 'total_amount' => $quote['rental_fee'] + $quote['deposit_amount']])->header('Cache-Control', 'no-store');
 }
 public function admin(Request $r) { $books=Book::with(['author','category','copies'])->when($r->q,fn($q)=>$q->where(function($query)use($r){$query->where('title','like','%'.$r->q.'%')->orWhere('book_code','like','%'.$r->q.'%')->orWhereHas('author',fn($a)=>$a->where('name','like','%'.$r->q.'%'));}))->when($r->author_id,fn($q)=>$q->where('author_id',$r->author_id))->when($r->category_id,fn($q)=>$q->where('category_id',$r->category_id))->when($r->status!==null && $r->status!=='',fn($q)=>$q->where('is_active',$r->status))->orderBy('book_code')->paginate(15);return view('books.admin',['books'=>$books,'authors'=>Author::orderBy('name')->get(),'categories'=>Category::orderBy('name')->get()]); }
 public function create(){return view('books.form',['book'=>new Book,'authors'=>Author::all(),'categories'=>Category::all()]);}
 public function edit(Book $book){return view('books.form',['book'=>$book,'authors'=>Author::all(),'categories'=>Category::all()]);}
 public function store(Request $r){Book::create($this->bookData($r));return redirect()->route('books.admin.index')->with('success','Đã thêm sách');}
 public function update(Request $r,Book $book){$book->update($this->bookData($r,$book->id,$book));return redirect()->route('books.admin.index')->with('success','Đã cập nhật sách');}
 private function bookData(Request $r,$id=null,?Book $book=null): array {
  $data=$this->rules($r,$id);
  if($r->hasFile('cover_image')){
   $data['cover_image']=$r->file('cover_image')->store('book-covers','public');
   if($book && $book->cover_image)Storage::disk('public')->delete($book->cover_image);
  }
  return $data;
 }
 private function rules(Request $r,$id=null){return $r->validate(['book_code'=>'required|max:30|unique:books,book_code'.($id?','.$id:''),'title'=>'required|max:255','author_id'=>'required|exists:authors,id','category_id'=>'required|exists:categories,id','publisher'=>'nullable|max:255','publication_year'=>'nullable|integer|between:1900,'.date('Y'),'cover_price'=>'required|integer|min:1','description'=>'nullable|string','cover_image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048','is_active'=>'required|boolean']);}
}
