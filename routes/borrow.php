<?php
use App\Http\Controllers\BorrowController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active'])->group(function(){Route::post('/borrow/{record}/cancel',[BorrowController::class,'cancel'])->name('borrow.cancel');});
Route::middleware(['auth','account.active','role:admin'])->group(function(){Route::get('/admin/borrow',[BorrowController::class,'index'])->name('borrow.index');Route::get('/admin/borrow/{record}/preview',[BorrowController::class,'preview'])->name('borrow.preview');Route::post('/admin/borrow/{record}/handover',[BorrowController::class,'handover'])->name('borrow.handover');});

Route::middleware(['auth','account.active','role:user'])->post('/reserve',[BorrowController::class,'reserve'])->name('borrow.reserve');
