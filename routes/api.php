<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MLMController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\Auth\Users\AuthController;


route::prefix('v1')->group(function () {

    route::post('login', [AuthController::class, 'login']);
    route::post('register', [AuthController::class, 'register']);



    route::middleware('auth:sanctum')->group(function () {
        // logout
        route::post('logout', [AuthController::class, 'logout']);

        // members
        Route::post('place-referral', [MLMController::class, 'placeReferral']);
        route::get('downline-members' , [MLMController::class , 'getDownlineMembers']);
        route::get('left-downline-members' , [MLMController::class , 'getLeftDownlineMembers']);
        route::get('right-downline-members' , [MLMController::class , 'getRightDownlineMembers']);
        route::get('downline-counts' , [MLMController::class , 'getDownlineCounts']);
        route::get('downlines-volume' , [MLMController::class , 'getNetworkVolume']);

        //packeage
        Route::get('packages', [PackageController::class, 'index']);
        route::get('my-package', [PackageController::class, 'show']);

        //subscription
        Route::post('subscribe', [SubscriptionController::class, 'store']);
    });
});
