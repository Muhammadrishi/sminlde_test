<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Orders group
Route::prefix('orders')->controller(OrderController::class)->group(function () {
    Route::post('/', 'store');
});
