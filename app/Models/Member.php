<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sponsor_id',
        'left_leg_id',
        'right_leg_id',
        'sales_volume',
        'rank'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
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





    public function calculateRank()
    {
        $downlineVolume = $this->getTotalDownlineVolume();
        $rank = 'Executive';

        if ($downlineVolume >= 100000) {
            $rank = 'Jade';
        } elseif ($downlineVolume >= 200000) {
            $rank = 'Sapphire';
        } elseif ($downlineVolume >= 300000) {
            $rank = 'Ruby';
        }
        // Add additional rank conditions

        $this->update(['rank' => $rank]);
    }



    public function getTotalDownlineVolume()
    {
        return $this->calculateLegVolume('left') + $this->calculateLegVolume('right');
    }



    private function calculateLegVolume($leg)
    {
        $volume = 0;
        $legMember = $leg === 'left' ? $this->leftLeg : $this->rightLeg;

        if ($legMember) {
            $volume += $legMember->sales_volume + $legMember->calculateLegVolume('left') + $legMember->calculateLegVolume('right');
        }

        return $volume;
    }
}
