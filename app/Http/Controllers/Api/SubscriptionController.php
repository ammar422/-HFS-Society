<?php

namespace App\Http\Controllers\Api;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Member;
use App\Models\Package;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    use ApiResponseTrait;
    public function store(StoreSubscriptionRequest $request)
    {

        $user = Auth::user();
        $member_id = $user->member->id;
        $member_ballance = $user->member->wallet_balance;
        $package = Package::find($request->package_id);
        $packege_price = $package->price;
        if ($member_ballance >= $packege_price) {

            $subscription = Subscription::create([
                'member_id' => $member_id,
                'package_id' => $request->package_id,
                'subscribed_at' => now(),
            ]);
            $member = $this->updateMemberBallnce($member_id, $packege_price);
            return $this->successResponse(
                'You have successfully subscribed ,current ballence is ' . $member->wallet_balance,
                'subscription',
                array_merge($subscription->toArray(), [
                    'member' => $member,
                    'packege_name' => $package->name
                ])
            );
        }
        return $this->failedResponse('Your current balance does not allow you to purchase this package');
    }

    private function updateMemberBallnce($member_id, $value)
    {
        $member = Member::find($member_id);
        $member->update([
            'wallet_balance' => $member->wallet_balance - $value
        ]);
        return $member;
    }
}
