<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::all();
        return response()->json($packages);
    }

    public function show()
    {
        $user = auth()->user();
        return $packages = $user->member->subscription->package;
    }
}
