<?php

use App\Http\Controllers\OrganizationStructureController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('struktur-organisasi', OrganizationStructureController::class)->name('struktur-organisasi');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
