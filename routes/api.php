<?php

use App\Adapters\Http\Controllers\AuthController;
use App\Adapters\Http\Controllers\BookController;
use App\Adapters\Http\Controllers\CategoryController;
use App\Adapters\Http\Controllers\MemberController;
use App\Adapters\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Identifiers are UUIDs: anything else is a 404 and never collides with literal paths (spec-gaps.md G-4).
Route::pattern('id', '[0-9a-fA-F-]{36}');

// api-contract.md §3.1 — public
Route::get('/health', fn () => ['status' => 'ok']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Everything else requires a token (CA-01.3)
Route::middleware('jwt')->group(function () {
    // §3.2 Users
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');

    // §3.3 Categories
    Route::get('/categories', [CategoryController::class, 'index']);

    // §3.4 Books
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::get('/books/{id}/cover', [BookController::class, 'cover']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);
        Route::put('/books/{id}/cover', [BookController::class, 'uploadCover']);
        Route::delete('/books/{id}/cover', [BookController::class, 'deleteCover']);
    });

    // §3.5 Members
    Route::middleware('role:admin,librarian')->group(function () {
        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::post('/members', [MemberController::class, 'store']);
    });
});
