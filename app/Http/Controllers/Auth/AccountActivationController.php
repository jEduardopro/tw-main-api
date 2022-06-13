<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountActivationController extends Controller
{
    public function activateAccount(Request $request)
    {
        $userActivation = DB::table('user_activations')->where('token', $request->token)->first();

        if (!$userActivation) {
            return response()->json([
                "errors" => [
                    "token" => ["The token is invalid"]
                ]
            ], 422);
        }

        $userActivationDate = Carbon::parse($userActivation->created_at);
        $currentDate = now()->subMinutes(2);
        if ($currentDate->greaterThan($userActivationDate)) {
            return response()->json([
                "errors" => [
                    "token" => ["The token is expired"]
                ]
            ], 422);
        }

        $user = User::where('id', $userActivation->user_id)->first();

        if (!$user) {
            return response()->json([
                'message' => "The account does not exist"
            ], 417);
        }

        if ($user->email) {
            $user->email_verified_at = now();
        }

        if ($user->phone) {
            $user->phone_verified_at = now();
            $user->updatePhoneValidated();
        }

        $user->save();

        DB::table('user_activations')->where('user_id', $user->id)->delete();

        $token = $user->createToken('token');

        return response()->json([
            "message" => "account activated successfully",
            "token" => $token->accessToken,
            "user" => $user
        ],200);
    }
}
