<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/work', [PageController::class, 'work'])->name('work');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

Route::get('/order', [OrderController::class, 'create'])->name('orders.create');
// Batas longgar untuk semua kiriman; batas order yang berhasil terkirim ada di OrderController::store.
Route::post('/order', [OrderController::class, 'store'])
    ->middleware('throttle:20,10')
    ->name('orders.store');
Route::get('/order/thanks/{code}', [OrderController::class, 'thanks'])->name('orders.thanks');

Route::get('/track', [OrderController::class, 'trackForm'])->name('orders.track');
Route::post('/track', [OrderController::class, 'track'])
    ->middleware('throttle:10,1')
    ->name('orders.track.lookup');
