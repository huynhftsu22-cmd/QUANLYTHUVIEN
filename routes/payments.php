<?php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active','role:admin'])->group(function(){
 Route::get('/admin/payments',[PaymentController::class,'index'])->name('payments.index');
 Route::patch('/admin/payments/{payment}/cancel',[PaymentController::class,'cancel'])->name('payments.cancel');
 Route::post('/admin/deposits/{transaction}/adjust',[PaymentController::class,'adjust'])->name('deposits.adjust');
});
