<?php

use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NewsController::class, 'home'])->name('home');
Route::get('/search', [NewsController::class, 'search'])->name('search');
Route::get('/category/{category:slug}', [NewsController::class, 'category'])->name('categories.show');
Route::get('/{slug}', [NewsController::class, 'show'])->name('posts.show');
