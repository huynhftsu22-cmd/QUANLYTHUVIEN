<?php
use App\Http\Controllers\StatisticController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active','role:admin'])->group(function(){Route::get('/admin/statistics',[StatisticController::class,'index'])->name('statistics.index');Route::get('/admin/statistics/export',[StatisticController::class,'export'])->name('statistics.export');});
