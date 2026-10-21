<?php
namespace App\Http\Controllers;
use App\Models\{Book,BookCopy,User,BorrowRecord,Payment,MembershipCard,DepositTransaction,StudentVerification};
use App\Exports\StatisticsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    private function range(Request $request): array
    {
        $data=$request->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
        return [$data['from']??today()->startOfMonth()->toDateString(),$data['to']??today()->toDateString()];
    }
    private function records(string $from,string $to)
    {
        return BorrowRecord::with(['user','bookCopy.book'])->whereDate('requested_at','>=',$from)->whereDate('requested_at','<=',$to)->orderBy('id')->get();
    }
    private function revenue(string $from,string $to): int
    {
        return (int)Payment::where('status','paid')->whereIn('type',['rental_fee','card_fee','late_fine','damage_fee'])->whereDate('paid_at','>=',$from)->whereDate('paid_at','<=',$to)->sum('amount');
    }
    private function bookBorrowCounts(string $from,string $to)
    {
        return Book::select('books.*')->selectSub(
            BorrowRecord::query()->join('book_copies','book_copies.id','=','borrow_records.book_copy_id')
                ->whereColumn('book_copies.book_id','books.id')
                ->whereIn('borrow_records.status',['borrowing','overdue','returned'])
                ->whereDate('borrow_records.borrow_date','>=',$from)->whereDate('borrow_records.borrow_date','<=',$to)
                ->selectRaw('COUNT(*)'), 'borrows_count'
        )->orderByDesc('borrows_count')->orderBy('books.book_code');
    }
    public function index(Request $request)
    {
        [$from,$to]=$this->range($request);
        $stats=['Đầu sách'=>Book::count(),'Bản sao'=>BookCopy::count(),'Độc giả'=>User::where('role','user')->count(),
            'Đang mượn'=>BorrowRecord::whereIn('status',['borrowing','overdue'])->count(),
            'Chờ bàn giao'=>BorrowRecord::where('status','pending')->count(),
            'Doanh thu trong kỳ (đ)'=>$this->revenue($from,$to),
            'Thẻ còn hạn'=>MembershipCard::where('status','active')->whereDate('expires_at','>=',today())->count(),
            'Thẻ sắp hết hạn (30 ngày)'=>MembershipCard::where('status','active')->whereBetween('expires_at',[today(),today()->addDays(30)])->count(),
            'Xác minh học sinh/sinh viên còn hạn'=>StudentVerification::where('status','approved')->whereDate('expires_at','>=',today())->count(),
            'Cọc đã hoàn trong kỳ (đ)'=>DepositTransaction::where('type','refund')->whereDate('processed_at','>=',$from)->whereDate('processed_at','<=',$to)->sum('amount'),
            'Cọc đang giữ (đ)'=>DepositTransaction::whereIn('type',['hold','adjust_up'])->sum('amount')-DepositTransaction::whereIn('type',['refund','deduct','adjust_down'])->sum('amount')];
        $recent=$this->records($from,$to)->take(20);
        $copyStatuses=BookCopy::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status');
        $topBooks=$this->bookBorrowCounts($from,$to)->paginate(10, ['*'], 'books_page')->withQueryString();
        $dailyRevenue=Payment::selectRaw('DATE(paid_at) as day, SUM(amount) as total')->where('status','paid')->whereIn('type',['rental_fee','card_fee','late_fine','damage_fee'])->whereBetween('paid_at',[$from.' 00:00:00',$to.' 23:59:59'])->groupByRaw('DATE(paid_at)')->orderBy('day')->get();
        return view('statistics.index',compact('stats','recent','from','to','copyStatuses','topBooks','dailyRevenue'));
    }
    public function export(Request $request)
    {
        [$from,$to]=$this->range($request);
        $borrow=[['Mã phiếu','Độc giả','Mã bản sao','Sách','Trạng thái','Ngày đặt','Ngày mượn','Hạn trả','Ngày trả','Phí thuê','Cọc','Phạt trễ','Bồi thường']];
        foreach($this->records($from,$to) as $r){$borrow[]=[(int)$r->id,$r->user?->name,$r->bookCopy?->copy_code,$r->bookCopy?->book?->title,config('saysach_labels.borrow_status.'.$r->status,'Chưa xác định'),(string)$r->requested_at,(string)$r->borrow_date,(string)$r->due_date,(string)$r->return_date,(int)$r->rental_fee,(int)$r->deposit_amount,(int)$r->fine_amount,(int)$r->damage_fee];}
        $books=[['Mã sách','Tên sách','Tác giả','Thể loại','Giá bìa','Tổng bản sao','Có sẵn','Đang mượn']];
        foreach(Book::with(['author','category','copies'])->orderBy('book_code')->get() as $book)$books[]=[$book->book_code,$book->title,$book->author?->name,$book->category?->name,(int)$book->cover_price,$book->copies->count(),$book->copies->where('status','available')->count(),$book->copies->where('status','borrowed')->count()];
        $payments=[['Mã thu','Loại','Độc giả','Số tiền','Phương thức','Ngày thu','Trạng thái']];
        foreach(Payment::with('user')->whereBetween('paid_at',[$from.' 00:00:00',$to.' 23:59:59'])->orderBy('id')->get() as $payment)$payments[]=[$payment->id,config('saysach_labels.payment_type.'.$payment->type,'Chưa xác định'),$payment->user?->name,(int)$payment->amount,config('saysach_labels.payment_method.'.$payment->method,'Chưa xác định'),(string)$payment->paid_at,config('saysach_labels.payment_status.'.$payment->status,'Chưa xác định')];
        $cards=[['Mã thẻ','Độc giả','Ngày cấp','Hết hạn','Tháng','Phí gần nhất','Trạng thái']];
        foreach(MembershipCard::with('user')->orderBy('id')->get() as $card)$cards[]=[$card->card_code,$card->user?->name,(string)$card->issued_at,(string)$card->expires_at,(int)$card->months,(int)$card->fee_amount,config('saysach_labels.card_status.'.$card->status,'Chưa xác định')];
        $summary=[['Chỉ tiêu','Giá trị'],['Từ ngày',$from],['Đến ngày',$to],['Đầu sách',Book::count()],['Bản sao',BookCopy::count()],['Độc giả',User::where('role','user')->count()],['Phiếu đang mượn',BorrowRecord::whereIn('status',['borrowing','overdue'])->count()],['Doanh thu đã thu (đ)',$this->revenue($from,$to)],['Cọc đang giữ (đ)',DepositTransaction::whereIn('type',['hold','adjust_up'])->sum('amount')-DepositTransaction::whereIn('type',['refund','deduct','adjust_down'])->sum('amount')]];
        $daily=[['Ngày','Doanh thu (đ)']];
        foreach(Payment::selectRaw('DATE(paid_at) as day, SUM(amount) as total')->where('status','paid')->whereIn('type',['rental_fee','card_fee','late_fine','damage_fee'])->whereBetween('paid_at',[$from.' 00:00:00',$to.' 23:59:59'])->groupByRaw('DATE(paid_at)')->orderBy('day')->get() as $d)$daily[]=[(string)$d->day,(int)$d->total];
        $copyStat=[['Trạng thái','Số bản sao']];
        foreach(BookCopy::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status') as $st=>$n)$copyStat[]=[config('saysach_labels.copy_status.'.$st,'Chưa xác định'),(int)$n];
        $top=[['Mã sách','Tên sách','Số lượt mượn trong kỳ']];
        foreach($this->bookBorrowCounts($from,$to)->get() as $b)$top[]=[$b->book_code,$b->title,(int)$b->borrows_count];
        $summary[]=['Thời điểm xuất',now()->format('Y-m-d H:i:s')];
        $sheets=['Tổng quan'=>$summary,'Doanh thu theo ngày'=>$daily,'Phiếu mượn – trả'=>$borrow,'Tình trạng bản sao'=>$copyStat,'Thống kê lượt mượn sách'=>$top];
        $name='thong_ke_'.$from.'_'.$to.'.xlsx';
        try{return Excel::download(new StatisticsExport($sheets),$name);}
        catch(\Throwable $e){report($e);return back()->with('error','Không thể xuất báo cáo, vui lòng thử lại.');}
    }
}
