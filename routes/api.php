<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MLMController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\Auth\Users\AuthController;

route::any('login', function () {
    return response()->json('you are unauthorized', 400);
})->name('login');


route::prefix('v1')->group(function () {

    route::post('login', [AuthController::class, 'login']);
    route::post('register', [AuthController::class, 'register']);



    route::middleware('auth:sanctum')->group(function () {
        // logout
        route::post('logout', [AuthController::class, 'logout']);

        // members
        Route::post('place-referral', [MLMController::class, 'placeReferral']);
        route::get('downline-members', [MLMController::class, 'getDownlineMembers']);
        route::get('left-downline-members', [MLMController::class, 'getLeftDownlineMembers']);
        route::get('right-downline-members', [MLMController::class, 'getRightDownlineMembers']);
        route::get('downline-counts', [MLMController::class, 'getDownlineCounts']);
        route::get('downlines-volume', [MLMController::class, 'getNetworkVolume']);

        //packeage
        Route::get('packages', [PackageController::class, 'index']);
        route::get('my-package', [PackageController::class, 'show']);

        //subscription
        Route::post('subscribe', [SubscriptionController::class, 'store']);

        //wallet
        route::get('current-balance', [SubscriptionController::class, 'myCurrentBalance']);
        route::get('all-tarnsactions', [SubscriptionController::class, 'myAllTarnsactions']);
        route::get('all-accetptd-tarnsactions', [SubscriptionController::class, 'myAcceptedTransactions']);
        route::get('all-rejected-tarnsactions', [SubscriptionController::class, 'myRejectedTransactions']);
        route::get('all-pending-tarnsactions', [SubscriptionController::class, 'myPendingTransactions']);
        route::get('all-withdrawal-tarnsactions', [SubscriptionController::class, 'myWithdrawalTransactions']);
        route::get('all-deposit-tarnsactions', [SubscriptionController::class, 'myDepositTransactions']);
        route::post('withdrawa', [SubscriptionController::class, 'withdrawal']);
    });
});
