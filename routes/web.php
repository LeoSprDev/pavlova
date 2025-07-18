<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirection automatique vers Filament
Route::get('/', function () {
    return redirect('/admin');
});

// Routes d'authentification désactivées - Filament gère l'authentification
// Route::get('/auth/login', [AuthController::class, 'showLogin'])->name('auth.login.form');
// Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
// Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
