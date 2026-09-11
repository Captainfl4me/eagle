<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReallocationController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Show dashboard for authenticated users, otherwise the welcome page
    return auth()->check() ? app(DashboardController::class)->index() : view('welcome');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Registration routes
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

// Profile routes (protected by auth middleware)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');

    // Budget routes (protected by auth)
    Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::get('/budgets/{id}', [BudgetController::class, 'show'])->name('budgets.show');
    Route::post('/budgets/{id}/month', [BudgetController::class, 'updateMonth'])->name('budgets.updateMonth');
    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Reallocation routes (protected by auth)
    Route::get('/budgets/{budget}/reallocations', [ReallocationController::class, 'index'])->name('reallocations.index');
    Route::post('/budgets/{budget}/reallocations', [ReallocationController::class, 'store'])->name('reallocations.store');
    Route::patch('/budgets/{budget}/reallocations/{reallocation}', [ReallocationController::class, 'update'])->name('reallocations.update');
    Route::delete('/budgets/{budget}/reallocations/{reallocation}', [ReallocationController::class, 'destroy'])->name('reallocations.destroy');
});
