<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    use ApiResponseTrait;



    public function index()
    {
        $users = User::paginate(5);
        return $this->successResponse('all users get successfully', 'users', $users);
    }



    public function usersWithMembership()
    {
        $users = User::paginate(5);
        $users->load('member');
        return $this->successResponse('all users get successfully', 'users', $users);
    }


    public function editUser(Request $request, $user_id)
    {
        $user = User::find($user_id);
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'unique:users,phone,' . $user->id],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }
        try {
            $user->update([
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
                'phone' => $request->phone ?? $user->phone
            ]);
            return $this->successResponse('user data updated successfully', 'user', $user);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }
    public function activeUser(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }
        try {
            $user->update([
                'status' => $request->status ?? $user->status
            ]);
            return $this->successResponse('user status updated successfully', 'user', $user);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function deleteUser($user_id)
    {
        $user = User::find($user_id);

        try {
            $user->delete();
            return response()->json([
                'status' => true,
                "message" => 'user deleted  successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }
}
