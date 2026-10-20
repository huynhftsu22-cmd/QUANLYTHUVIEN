<?php
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;
Route::group([],function(){Route::get('/books',[BookController::class,'index'])->name('books.index');Route::get('/books/{book}',[BookController::class,'show'])->name('books.show');});
Route::middleware(['auth','account.active','role:admin'])->prefix('admin')->group(function(){Route::get('books',[BookController::class,'admin'])->name('books.admin.index');Route::get('books/create',[BookController::class,'create'])->name('books.create');Route::post('books',[BookController::class,'store'])->name('books.store');Route::get('books/{book}/edit',[BookController::class,'edit'])->name('books.edit');Route::put('books/{book}',[BookController::class,'update'])->name('books.update');});

Route::middleware(['auth','account.active','role:user'])->get('/books/{book}/quote', [BookController::class,'quote'])->name('books.quote');
