<?php

use App\Http\Controllers\WebManifest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('{locale}')->middleware(\App\Http\Middleware\SetLocaleFromUrl::class)->group(function () {
    Route::get('/manifest.json', [WebManifest::class, 'get'])->name('webmanifest');
});

foreach (config('nt.book_url') as $bookId => $slug) {
    Route::get($slug, [\App\Http\Controllers\Book::class, 'get'])->defaults('bookId', $bookId)->name('book_' . $bookId);
}
