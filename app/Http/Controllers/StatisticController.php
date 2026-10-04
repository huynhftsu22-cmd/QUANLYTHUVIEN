<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowRecord;
use Illuminate\View\View;

class StatisticController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_books' => Book::count(),
            'borrowed_copies' => BorrowRecord::whereIn('status', ['borrowing', 'overdue'])->count(),
            'borrowing' => BorrowRecord::where('status', 'borrowing')->count(),
            'overdue' => BorrowRecord::where('status', 'overdue')->count(),
            'returned' => BorrowRecord::where('status', 'returned')->count(),
            'fine_total' => BorrowRecord::where('fine_amount', '>', 0)->sum('fine_amount'),
            'fine_paid' => BorrowRecord::where('fine_amount', '>', 0)
                ->where('is_paid', true)
                ->sum('fine_amount'),
            'fine_unpaid' => BorrowRecord::where('fine_amount', '>', 0)
                ->where('is_paid', false)
                ->sum('fine_amount'),
        ];

        $popular = Book::withCount('borrowRecords')
            ->orderByDesc('borrow_records_count')
            ->limit(10)
            ->get();

        $overdueRecords = BorrowRecord::with(['user', 'book'])
            ->where('status', 'overdue')
            ->latest('due_date')
            ->get();

        return view('statistics.index', compact(
            'stats',
            'popular',
            'overdueRecords'
        ));
    }
}