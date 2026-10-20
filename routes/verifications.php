<?php
use App\Http\Controllers\StudentVerificationController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active','role:user'])->group(function(){Route::get('/student-verification',[StudentVerificationController::class,'mine'])->name('verifications.mine');Route::post('/student-verification',[StudentVerificationController::class,'store'])->name('verifications.store');});
Route::middleware(['auth','account.active','role:admin'])->group(function(){Route::get('/admin/verifications',[StudentVerificationController::class,'index'])->name('verifications.index');Route::patch('/admin/verifications/{verification}',[StudentVerificationController::class,'review'])->name('verifications.review');});

Route::middleware(['auth','account.active'])->get('/student-verification/{verification}/evidence',[StudentVerificationController::class,'evidence'])->name('verifications.evidence');

Route::middleware(['auth','account.active','role:admin'])->group(function () {
    Route::get('/admin/verifications/{verification}/delete',[StudentVerificationController::class,'confirmDelete'])->name('verifications.confirm-delete');
    Route::delete('/admin/verifications/{verification}',[StudentVerificationController::class,'destroy'])->name('verifications.destroy');
});
