<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'sponsor_id' => 1,
            'left_leg_id' => null,
            'right_leg_id' => null,
            'sales_volume' => 0,
            'rank' => 'super man',
        ];
    }
}
