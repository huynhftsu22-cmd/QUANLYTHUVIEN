<?php
use App\Http\Controllers\MembershipCardController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','account.active','role:admin'])->group(function(){Route::get('/admin/cards',[MembershipCardController::class,'index'])->name('cards.index');Route::post('/admin/cards',[MembershipCardController::class,'store'])->name('cards.store');Route::patch('/admin/cards/{card}/status',[MembershipCardController::class,'status'])->name('cards.status');});

Route::middleware(['auth','account.active','role:user'])->get('/my-membership', [MembershipCardController::class,'mine'])->name('cards.mine');

Route::middleware(['auth','account.active','role:user'])->group(function () {
    Route::post('/my-membership/requests', [\App\Http\Controllers\MembershipRequestController::class,'store'])->name('cards.requests.store');
    Route::post('/my-membership/requests/{registration}/cancel', [\App\Http\Controllers\MembershipRequestController::class,'cancel'])->name('cards.requests.cancel');
});
Route::middleware(['auth','account.active','role:admin'])->post('/admin/cards/requests/{registration}/review', [\App\Http\Controllers\MembershipRequestController::class,'review'])->name('cards.requests.review');
