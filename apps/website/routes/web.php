<?php

use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('struktur-organisasi', OrganizationStructureController::class)->name('struktur-organisasi');

Route::get('berita', [PostController::class, 'index'])->name('berita.index');
Route::get('berita/{post}', [PostController::class, 'show'])->name('berita.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
