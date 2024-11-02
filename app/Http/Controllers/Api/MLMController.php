<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterMemberRequest;
use App\Http\Requests\PlaceReferralRequest;
use App\Models\Member;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class MLMController extends Controller
{
    use ApiResponseTrait;




    public function placeReferral(PlaceReferralRequest $request)
    {

        $user = auth()->user();
        $sponsor = $user->member;

        $referral = Member::findOrFail($request->referral_id);

        // need to implement referralRestrictionsFunction to check the referral id != sponsor id and referral id != 1 and the sponsor right_leg_id != left_leg_id 


        if ($request->placement === 'left' && !$sponsor->left_leg_id && $sponsor->id !== $referral->id) {
            $sponsor->left_leg_id = $referral->id;
        } elseif ($request->placement === 'right' && !$sponsor->right_leg_id && $sponsor->id !== $referral->id) {
            $sponsor->right_leg_id = $referral->id;
        } else {
            return response()->json(['error' => 'No available slot on the selected sponser leg , or the sponser id = referral id'], 400);
        }

        $sponsor->save();

        return $this->successResponse(
            "The referral : "  . $referral->user->name . " has been successfully added to Spencer : " . $sponsor->user->name . " in " . $request->placement .  " leg",
            'sponsor',
            $sponsor
        );
    }




    public function calculateEarnings(Member $member): JsonResponse
    {
        $leftVolume = $this->calculateLegVolume($member, 'left');
        $rightVolume = $this->calculateLegVolume($member, 'right');
        $earnings = min($leftVolume, $rightVolume) * 0.10; // Assuming a 10% commission

        return response()->json(['earnings' => $earnings, 'leftVolume' => $leftVolume, 'rightVolume' => $rightVolume]);
    }





    private function calculateLegVolume(Member $member, $leg)
    {
        $volume = 0;
        $legMember = $leg === 'left' ? $member->leftLeg : $member->rightLeg;

        if ($legMember) {
            $volume += $legMember->sales_volume + $this->calculateLegVolume($legMember, 'left') + $this->calculateLegVolume($legMember, 'right');
        }

        return $volume;
    }
}
