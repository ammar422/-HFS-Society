<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\Package;
use App\Models\Subscription;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreSubscriptionRequest;

class SubscriptionController extends Controller
{
    use ApiResponseTrait;



    // public function store(StoreSubscriptionRequest $request)
    // {

    //     $user = Auth::user();
    //     $member = $user->member;
    //     $userTank = $member->userTank;
    //     $member_ballance = $member->wallet->balance;
    //     $package = Package::find($request->package_id);
    //     $packege_price = $package->price;


    //     if ($member_ballance >= $packege_price) {
    //         if ($member->subscription)
    //             return $this->failedResponse('You are currently subscribed to a package. Please unsubscribe from the current package first');
    //         try {
    //             DB::beginTransaction();
    //             $subscription = Subscription::create([
    //                 'member_id' => $member->id,
    //                 'package_id' => $request->package_id,
    //                 'subscribed_at' => now(),
    //             ]);
    //             if ($userTank)
    //                 $userTank->delete();
    //             // $member->update(['current_cv' => $package->cv]);
    //             $newBalance = $this->updateMemberWallatBallnce($member, $packege_price);
    //             DB::commit();
    //             return $this->successResponse(
    //                 'You have successfully subscribed ,current ballence is ' . $newBalance,
    //                 'subscription',
    //                 array_merge($subscription->toArray(), [
    //                     'member_name' => $member->user->name,
    //                     'sponsor' => $member->sponsor->user->name,
    //                     'packege_name' => $package->name
    //                 ])
    //             );
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             return $this->failedResponse('Your current balance does not allow you to purchase this package');
    //         }
    //     }
    // }



    public function store(StoreSubscriptionRequest $request)
    {
        $user = Auth::user();
        $member = $user->member;
        $userTank = $member->userTank;
        $member_balance = $member->wallet->balance;
        $package = Package::find($request->package_id);

        if (!$package) {
            return $this->failedResponse('Package not found.');
        }

        $package_price = $package->price;

        // Check if member balance is sufficient  
        if ($member_balance < $package_price) {
            return $this->failedResponse('Insufficient balance to subscribe to this package.');
        }

        // Check if the member is already subscribed  
        if ($member->subscription) {
            return $this->failedResponse('You are currently subscribed to a package. Please unsubscribe from the current package first');
        }

        try {
            DB::beginTransaction();

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'package_id' => $request->package_id,
                'subscribed_at' => now(),
            ]);

            if ($userTank) {
                $userTank->delete();
            }

            $member->update(['current_cv' => $package->cv]);
            // Update the member's wallet balance  
            $newBalance = $this->updateMemberWallatBallnce($member, $package_price);

            DB::commit();

            return $this->successResponse(
                'You have successfully subscribed. Current balance is ' . $newBalance,
                'subscription',
                array_merge($subscription->toArray(), [
                    'member_name' => $member->user->name,
                    'sponsor' => $member->sponsor->user->name,
                    'package_name' => $package->name
                ])
            );
        } catch (\Exception $e) {
            DB::rollBack();       
            return $this->failedResponse('An error occurred while processing your subscription.');
        }
    }

    private function updateMemberWallatBallnce(Member $member, $value)
    {
        $wallet = $member->wallet;
        $wallet->update([
            'balance' => $wallet->balance - $value
        ]);
        return $wallet->balance;
    }
}
