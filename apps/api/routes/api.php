<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TourController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('categories', [CategoryController::class, 'index']);

Route::get('tours/featured', [TourController::class, 'featured']);
Route::get('tours/{slug}', [TourController::class, 'show'])->where('slug', '[a-z0-9-]+');
Route::get('tours', [TourController::class, 'index']);

Route::post('search', SearchController::class)->middleware('throttle:30,1');
