<?php

namespace App\Http\Controllers\Api\Auth\Users;

use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterMemberRequest;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {

            $oldToken = $user->tokens();
            if ($oldToken)
                $oldToken->delete();

            return response()->json([
                'status' => true,
                'message' => 'login successfully ',
                'token' => ($user->createToken('user token'))->plainTextToken,
                'user' => $user
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'The provided credentials are incorrect.'
        ], 400);
    }





    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed'],
            'sponsor_id' => ['required', 'exists:members,id']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }
        try {
            DB::beginTransaction();

            $user = User::create($validator->validated());

            $member = Member::create([
                'user_id' => $user->id,
                'sponsor_id' => $request->sponsor_id
            ]);

            DB::commit();

            $user = array_merge($user->toArray(), [
                'token' => ($user->createToken('user token'))->plainTextToken,
                'member' => $member
            ]);
            if ($user) {
                return $this->successResponse('user registered successfully', 'user', $user, 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $e;
            return $this->failedResponse();
        }
    }


    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return response()->json([
            'status' => true,
            'message' => 'user loged out successfuly'
        ]);
    }
}
