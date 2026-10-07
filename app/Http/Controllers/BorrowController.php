<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowRecord;
use App\Services\BorrowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BorrowController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private readonly BorrowService $service) {}

    /** UC04 – Độc giả mượn sách (POST /books/{book}/borrow). */
    public function borrow(Request $request, Book $book): RedirectResponse
    {
        $record = $this->service->borrow($request->user(), $book);

        return back()->with('success', 'Mượn sách thành công. Hạn trả là '.$record->due_date->format('d/m/Y').'.');
    }

    /** UC09 – Danh sách + bộ lọc phiếu mượn (GET /admin/borrow-records). */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $keyword = $request->query('q');
        $keyword = is_string($keyword) ? trim($keyword) : '';

        $records = BorrowRecord::with(['user', 'book'])
            // Trạng thái không hợp lệ thì bỏ qua bộ lọc (luồng ngoại lệ E3)
            ->when(is_string($status) && in_array($status, BorrowRecord::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($request->boolean('unpaid'), fn ($q) => $q->unpaidFine())
            ->when($keyword !== '', function ($q) use ($keyword) {
                $term = '%'.addcslashes($keyword, '\\%_').'%';
                $q->where(fn ($sub) => $sub
                    ->whereHas('user', fn ($u) => $u->where('user_code', 'like', $term))
                    ->orWhereHas('book', fn ($b) => $b->where('book_code', 'like', $term)));
            })
            ->orderByDesc('borrow_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('borrow.index', ['records' => $records]);
    }

    /** UC06 – Admin xác nhận trả sách (PATCH /admin/borrow-records/{id}/return). */
    public function returnBook(Request $request, BorrowRecord $borrowRecord): RedirectResponse
    {
        $this->service->returnBook($borrowRecord, $request->boolean('is_paid'));

        return back()->with('success', 'Đã xác nhận trả sách.');
    }

    /** UC09 – Admin xác nhận thu phạt (PATCH /admin/borrow-records/{id}/pay). */
    public function pay(BorrowRecord $borrowRecord): RedirectResponse
    {
        $this->service->markFinePaid($borrowRecord);

        return back()->with('success', 'Đã xác nhận thu tiền phạt.');
    }
}
