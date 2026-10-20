<?php
use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    if (!auth()->check()) return redirect()->route('login');
    return auth()->user()->role === 'admin' ? redirect()->route('statistics.index') : redirect()->route('books.index');
})->name('home');
foreach (['auth','cards','books','copies','history','authors','categories','users','verifications','statistics','borrow','returns','payments'] as $module) {
    $file=__DIR__.'/'.$module.'.php';
    if (file_exists($file)) require $file;
}
