<?php

use Illuminate\Support\Facades\Route;

// api-contract.md §3.1 — public
Route::get('/health', fn () => ['status' => 'ok']);
