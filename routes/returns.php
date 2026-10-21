<?php
use App\Http\Controllers\ReturnController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active','role:admin'])->group(function(){Route::get('/admin/returns',[ReturnController::class,'index'])->name('returns.index');Route::post('/admin/returns/{record}/preview',[ReturnController::class,'preview'])->name('returns.preview');Route::post('/admin/returns/{record}',[ReturnController::class,'store'])->name('returns.store');});
