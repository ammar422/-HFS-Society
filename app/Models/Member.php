<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sponsor_id',
        'left_leg_id',
        'right_leg_id',
        'current_cv',
        'totla_left_volume',
        'totla_right_volume',
        'rank',
        'total_commision'
    ];

    // protected $hidden = ['left_leg', 'right_leg'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function commission(): HasMany
    {
        return $this->hasMany(Commission::class, 'sponsor_id', 'id');
    }


    public function commissionReferral(): HasMany
    {
        return $this->hasMany(Commission::class, 'referral_id', 'id');
    }


    public function sponsor()
    {
        return $this->belongsTo(Member::class, 'sponsor_id');
    }


    public function leftLeg()
    {
        return $this->belongsTo(Member::class, 'left_leg_id');
    }


    public function rightLeg()
    {
        return $this->belongsTo(Member::class, 'right_leg_id');
    }

    /**
     * Relationship with UserTank model
     * A Member may be in the UserTank table if they haven't purchased a package.
     */
    public function userTank()
    {
        return $this->hasOne(UserTank::class);
    }

    /**
     * Relationship with Wallet model
     * Each Member has a wallet.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Relationship with Subscription model
     * A Member may have one active subscription after purchasing a package.
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }


    /**
     * Calculates the count of downline members on the left leg.
     */
    public function countLeftDownline()
    {
        return $this->countDownline($this->left_leg_id);
    }

    /**
     * Calculates the count of downline members on the right leg.
     */
    public function countRightDownline()
    {
        return $this->countDownline($this->right_leg_id);
    }

    /**
     * Recursive function to calculate downline count.
     */
    private function countDownline($legId)
    {
        if (!$legId) {
            return 0;
        }
        $member = self::find($legId);
        return 1 + $member->countLeftDownline() + $member->countRightDownline();
    }



    /**
     * Calculates the total network volume for the left leg.
     */
    public function calculateLeftVolume()
    {
        return $this->calculateVolume($this->left_leg_id);
    }

    /**
     * Calculates the total network volume for the right leg.
     */
    public function calculateRightVolume()
    {
        return $this->calculateVolume($this->right_leg_id);
    }

    /**
     * Recursive function to calculate network volume for a leg.
     */
    private function calculateVolume($legId)
    {
        if (!$legId) {
            return 0;
        }
        $member = self::find($legId);
        $volume = $member->current_cv;
        return $volume + $member->calculateLeftVolume() + $member->calculateRightVolume();
    }

    public function subscriptionPrice()
    {
        return $this->subscription->package->price;
    }





    // Recursive method to fetch all uplines
    public function getAllUplines()
    {
        $uplines = [];
        $sponsor = $this->sponsor;

        while ($sponsor) {
            $uplines[] = $sponsor;
            $sponsor = $sponsor->sponsor;
        }

        return $uplines;
    }

    // Method to get the count of all uplines
    public function getUplineCount()
    {
        return count($this->getAllUplines());
    }




}
