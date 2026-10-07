<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Hiển thị lịch sử mượn sách của độc giả đang đăng nhập.
     */
    public function index(Request $request): View
    {
        $records = $request->user()
            ->borrowRecords()
            ->with('book')
            ->latest('borrow_date')
            ->paginate(15);

        return view('history.index', compact('records'));
    }
}