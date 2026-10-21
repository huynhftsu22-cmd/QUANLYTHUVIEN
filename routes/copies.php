<?php
use App\Http\Controllers\BookCopyController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active','role:admin'])->group(function(){Route::get('/admin/books/{book}/copies',[BookCopyController::class,'index'])->name('copies.index');Route::post('/admin/books/{book}/copies',[BookCopyController::class,'store'])->name('copies.store');Route::patch('/admin/copies/{copy}',[BookCopyController::class,'update'])->name('copies.update');});
