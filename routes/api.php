<?php

use App\Adapters\Http\Controllers\AuthController;
use App\Adapters\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// api-contract.md §3.1 — public
Route::get('/health', fn () => ['status' => 'ok']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Everything else requires a token (CA-01.3)
Route::middleware('jwt')->group(function () {
    // §3.2 Users
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');
});
