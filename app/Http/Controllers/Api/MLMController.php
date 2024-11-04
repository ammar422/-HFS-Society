<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterMemberRequest;
use App\Http\Requests\PlaceReferralRequest;
use App\Models\Commission;
use App\Models\CommissionFactor;
use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MLMController extends Controller
{
    use ApiResponseTrait;




    // public function placeReferral(PlaceReferralRequest $request)
    // {

    //     $user = auth()->user();
    //     $sponsor = $user->member;
    //     $referral = Member::findOrFail($request->referral_id);

    //     if (!$referral->subscription)
    //         return $this->failedResponse('sorry this referral not subscribed to any packages');

    //     if (
    //         $referral->id == 1 ||
    //         $referral->id == $sponsor->id ||
    //         $referral->id == $sponsor->left_leg_id ||
    //         $referral->id == $sponsor->right_leg_id
    //     )
    //         return $this->failedResponse(config('consts.REFERRAL_BLOCK_MESSAGE', 'This process cannot be completed'));

    //     if ($referral->sponsor->id !== $sponsor->id)
    //         return $this->failedResponse('sorry this referral belongs to another sponsor');


    //     if ($request->placement === 'left' && !$sponsor->left_leg_id) {
    //         $sponsor->left_leg_id = $referral->id;
    //     } elseif ($request->placement === 'right' && !$sponsor->right_leg_id) {
    //         $sponsor->right_leg_id = $referral->id;
    //     } else {
    //         return response()->json(['error' => 'No available slot on the selected sponser leg'], 400);
    //     }


    //     $packagePrice = $referral->subscriptionPrice();
    //     $rate = (CommissionFactor::first())->direct_rate;
    //     $commissionValue =  ($packagePrice * $rate) / 100;
    //     $sponsor->total_commision += $commissionValue;
    //     $uplines = $referral->getAllUplines();
    //     try {
    //         DB::beginTransaction();
    //         Commission::create([
    //             'sponsor_id' => $sponsor->id,
    //             'commission_value' => $commissionValue,
    //             'commission_type' => 'direct',
    //             'referral_id' => $referral->id,
    //         ]);

    //         $sponsor->save();
    //         DB::commit();


    //         return $this->successResponse(
    //             "The referral : "  . $referral->user->name . " has been successfully added to Spencer : " . $sponsor->user->name . " in " . $request->placement .  " leg",
    //             'sponsor',
    //             $sponsor
    //         );
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         // return $e;
    //         return $this->failedResponse('sorry This process cannot be completed', 500);
    //     }
    // }
    public function placeReferral(PlaceReferralRequest $request)
    {
        $user = auth()->user();
        $sponsor = $user->member;
        $referral = Member::findOrFail($request->referral_id);

        if (!$referral->subscription)
            return $this->failedResponse('sorry this referral not subscribed to any packages');

        if (
            $referral->id == 1 ||
            $referral->id == $sponsor->id ||
            $referral->id == $sponsor->left_leg_id ||
            $referral->id == $sponsor->right_leg_id
        )
            return $this->failedResponse(config('consts.REFERRAL_BLOCK_MESSAGE', 'This process cannot be completed'));

        if ($referral->sponsor->id !== $sponsor->id)
            return $this->failedResponse('sorry this referral belongs to another sponsor');

        if ($request->placement === 'left' && !$sponsor->left_leg_id) {
            $sponsor->left_leg_id = $referral->id;
            $sponsor->totla_left_volume += $referral->current_cv;
        } elseif ($request->placement === 'right' && !$sponsor->right_leg_id) {
            $sponsor->right_leg_id = $referral->id;
            $sponsor->totla_right_volume += $referral->current_cv;
        } else {
            return response()->json(['error' => 'No available slot on the selected sponsor leg'], 400);
        }

        $packagePrice = $referral->subscriptionPrice();
        $rate = (CommissionFactor::first())->direct_rate;
        $commissionValue = ($packagePrice * $rate) / 100;
        $sponsor->total_commision += $commissionValue;

        $uplines = $referral->getAllUplines();
        $currentCv = $referral->current_cv;

        try {
            DB::beginTransaction();

            // Create commission record
            Commission::create([
                'sponsor_id' => $sponsor->id,
                'commission_value' => $commissionValue,
                'commission_type' => 'direct',
                'referral_id' => $referral->id,
            ]);

            $sponsor->save();

            foreach ($uplines as $upline) {
                if ($upline->left_leg_id === $referral->id || $upline->left_leg_id == $sponsor->id) {
                    $upline->totla_left_volume += $currentCv;
                } elseif ($upline->right_leg_id === $referral->id || $upline->right_leg_id == $sponsor->id) {
                    $upline->totla_right_volume += $currentCv;
                }

                $referral = $upline;
                $upline->save();
            }

            DB::commit();

            return $this->successResponse(
                "The referral: " . $referral->user->name . " has been successfully added to Sponsor: " . $sponsor->user->name . " in " . $request->placement . " leg",
                'sponsor',
                $sponsor
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse('Sorry, this process cannot be completed', 500);
        }
    }




    public function getDownlineMembers()
    {
        $user = auth()->user();

        $member = $user->member;

        $leftLegMembers = [];
        $rightLegMembers = [];

        $queue = [];

        if ($member->leftLeg) {
            $queue[] = ['member' => $member->leftLeg, 'side' => 'left'];
        }

        if ($member->rightLeg) {
            $queue[] = ['member' => $member->rightLeg, 'side' => 'right'];
        }

        while (!empty($queue)) {
            $current = array_shift($queue);
            $currentMember = $current['member'];
            $currentSide = $current['side'];

            if ($currentMember) {
                if ($currentSide === 'left') {
                    $leftLegMembers[] = $currentMember;
                } else {
                    $rightLegMembers[] = $currentMember;
                }

                if ($currentMember->leftLeg) {
                    $queue[] = ['member' => $currentMember->leftLeg, 'side' => 'left'];
                }
                if ($currentMember->rightLeg) {
                    $queue[] = ['member' => $currentMember->rightLeg, 'side' => 'right'];
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Downline members retrieved successfully.',
            'data' => [
                'leftLeg' => $leftLegMembers,
                'rightLeg' => $rightLegMembers,
            ],
        ], 200);
    }



    public function getLeftDownlineMembers()
    {
        $user = auth()->user();
        $member = $user->member;
        $leftDownlineMembers = [];
        $queue = [$member->leftLeg];
        while (!empty($queue)) {
            $currentMember = array_shift($queue);

            if ($currentMember) {
                $leftDownlineMembers[] = $currentMember;

                if ($currentMember->leftLeg) {
                    $queue[] = $currentMember->leftLeg;
                }
                if ($currentMember->rightLeg) {
                    $queue[] = $currentMember->rightLeg;
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Left downline members retrieved successfully.',
            'data' => $leftDownlineMembers,
        ], 200);
    }



    public function getRightDownlineMembers()
    {
        $user = auth()->user();
        $member = $user->member;
        $rightDownlineMembers = [];
        $queue = [$member->rightLeg];
        while (!empty($queue)) {
            $currentMember = array_shift($queue);

            if ($currentMember) {
                $rightDownlineMembers[] = $currentMember;

                if ($currentMember->rightLeg) {
                    $queue[] = $currentMember->rightLeg;
                }
                if ($currentMember->rightLeg) {
                    $queue[] = $currentMember->leftLeg;
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'right downline members retrieved successfully.',
            'data' => $rightDownlineMembers,
        ], 200);
    }


    /**
     * Returns the number of downlines on the left and right legs.
     */
    public function getDownlineCounts()
    {
        $user = auth()->user();
        $member = $user->member;
        $data = [];
        $data['left_downlines_count'] = $member->countLeftDownline();
        $data['right_downlines_count'] = $member->countRightDownline();
        return $this->successResponse('done successfully', 'count', $data);
    }


    /**
     * Returns the network volume on the left and right legs.
     */
    public function getNetworkVolume()
    {
        $user = auth()->user();
        $member = $user->member;
        $data['left_leg_volume'] = $member->calculateLeftVolume();
        $data['right_leg_volume'] = $member->calculateRightVolume();
        return response()->json([
            $data
        ]);
    }




    /**
     * Calculates the direct commission for a member and returns it.
     */
    public function calculateDirectCommission()
    {
        $user = auth()->user();
        $member = $user->member;
        $directCommission = $member->calculateDirectCommission();
        return response()->json([
            'direct_commission' => $directCommission,
            'balance' => $member->balance,
        ]);
    }
}
