<?php

namespace App\Http\Controllers\Api\Auth\Users;

use App\Models\User;
use App\Models\Member;
use App\Models\Wallet;
use App\Models\UserTank;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            'sponsor_id' => ['required', 'exists:members,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Step 1: Create the user
            $userData = $validator->validated();
            $userData['password'] = bcrypt($userData['password']); // Hash password
            $user = User::create($userData);

            // Step 2: Add the user to the members table
            $member = Member::create([
                'user_id' => $user->id,
                'sponsor_id' => $request->sponsor_id,
            ]);

            // Step 3: Add the new member to the UserTank
            UserTank::create([
                'member_id' => $user->id,
                'sponsor_id' => $request->sponsor_id,
            ]);

            // Step 4: Create an empty wallet for the member
            Wallet::create([
                'member_id' => $member->id,
                'balance' => 0.00, // Initial balance is zero
            ]);

            DB::commit();

            // Return user data with access token and member info
            $user = array_merge($user->toArray(), [
                'token' => $user->createToken('user token')->plainTextToken,
                'member' => $member,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
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


    public function sponsorData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sponsor_id' => ['required', 'exists:members,id'],
        ]);
        if ($validator->fails())
            return $this->failedResponse($validator->errors(), 422);

        $sponser = Member::find($request->sponsor_id);
        $sponser_name = $sponser->user->name;
        return $this->successResponse('sponsor data get successfully', 'sponsor name', $sponser_name);
    }


    public function userProfile()
    {
        $user = (auth()->user());
        $user->load('member');
        return response()->json([
            'status' => true,
            'message' => 'user data get successfully',
            'user data' => $user
        ]);
    }

    public function editUserProfile(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'name' =>  ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'unique:users,phone,' . $user->id]
        ]);
        if ($validator->fails()) {
            return $this->failedResponse($validator->errors(), 422);
        }
        try {
            $request->name ? $user->name = $request->name : $user->name;
            $request->phone ? $user->phone = $request->phone : $user->phone;
            $user->save();
            return $this->successResponse('user data updated successfully ', 'user', $user);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function deleteMyUser()
    {
        $user = auth()->user();
        try {
            $user->delete();
            return response()->json([
                'status' => true,
                'message' => 'user deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function activeUser()
    {
        $user = auth()->user();
        try {
            $user->status = 'active';
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'user status activated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function inactiveUser()
    {
        $user = auth()->user();
        try {
            $user->status = 'inactive';
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'user status inactivated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    
}
