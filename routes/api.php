<?php

use App\Http\Controllers\Api\Auth\Users\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MLMController;


route::prefix('v1')->group(function () {

    route::post('login', [AuthController::class, 'login']);
    route::post('register', [AuthController::class, 'register']);



    route::middleware('auth:sanctum')->group(function () {
        // logout
        route::post('logout', [AuthController::class, 'logout']);

        // referral
        Route::post('place-referral', [MLMController::class, 'placeReferral']);
        Route::get('/{member}/earnings', [MLMController::class, 'calculateEarnings']);
    });
});
