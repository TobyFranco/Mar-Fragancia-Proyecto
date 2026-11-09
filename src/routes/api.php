<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/auth/me', fn (Request $request) => $request->user())->middleware('auth:sanctum');
});
