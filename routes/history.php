<?php
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active'])->get('/history',[HistoryController::class,'index'])->name('history.index');
