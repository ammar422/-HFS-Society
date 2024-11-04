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
use App\Http\Requests\StoreWalletTransaction;
use App\Models\WalletTransaction;

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

    public function myCurrentBalance()
    {
        $user = Auth::user();
        $member = $user->member;
        $balance = $member->wallet->balance;
        return response()->json([
            'status' => true,
            'message' => 'you current wallet balance is ' . $balance . ' USD'
        ]);
    }

    public function withdrawal(StoreWalletTransaction $request)
    {
        $user = Auth::user();
        $wallet = $user->member->wallet;
        if ($wallet->balance < $request->amount)
            return $this->failedResponse('Insufficient balance , whice your balance is :: ' . $wallet->balance . " USD");

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount
        ]);
        // handel notification to the admin to approve or reject the request |
        if ($transaction)
            return $this->successResponse('your request has been sent succcessfully', 'transaction', $transaction);
        return $this->failedResponse();
    }

    public function myAllTarnsactions()
    {
        $user = Auth::user();
        $tarnsactions = $user->member->wallet->tarnsactions()->paginate(5);
        if ($tarnsactions)
            return $this->successResponse('all tarnsactions get successfuly', 'tarnsactions', $tarnsactions);
        return $this->failedResponse();
    }



    public function myAcceptedTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $tarnsactions = WalletTransaction::where('wallet_id', $wallet_id)->where('status', 'accepted')->paginate(5);

        if ($tarnsactions)
            return $this->successResponse('All accepted transactions fetched successfully', 'transactions', $tarnsactions);

        return $this->failedResponse();
    }

    public function myRejectedTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $tarnsactions = WalletTransaction::where('wallet_id', $wallet_id)->where('status', 'rejected')->paginate(5);

        if ($tarnsactions)
            return $this->successResponse('All rejected transactions fetched successfully', 'transactions', $tarnsactions);

        return $this->failedResponse();
    }


    public function myPendingTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $tarnsactions = WalletTransaction::where('wallet_id', $wallet_id)->where('status', 'pending')->paginate(5);

        if ($tarnsactions)
            return $this->successResponse('All rejected transactions fetched successfully', 'transactions', $tarnsactions);

        return $this->failedResponse();
    }


    public function myWithdrawalTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $tarnsactions = WalletTransaction::where('wallet_id', $wallet_id)->where('transaction_type', 'withdrawal')->paginate(5);

        if ($tarnsactions)
            return $this->successResponse('All withdrawal transactions fetched successfully', 'transactions', $tarnsactions);

        return $this->failedResponse();
    }

    public function myDepositTransactions()
    {
        $user = Auth::user();
        $wallet_id =  $user->member->wallet->id;

        $tarnsactions = WalletTransaction::where('wallet_id', $wallet_id)->where('transaction_type', 'deposit')->paginate(5);

        if ($tarnsactions)
            return $this->successResponse('All deposit transactions fetched successfully', 'transactions', $tarnsactions);

        return $this->failedResponse();
    }
}
