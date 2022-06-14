<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountActivationFormRequest;
use App\Http\Requests\ResendActivationCodeFormRequest;
use App\Models\User;
use App\Notifications\VerifyEmailActivation;
use App\Notifications\VerifyPhoneActivation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;

class AccountActivationController extends Controller
{
    public function activateAccount(AccountActivationFormRequest $request)
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
        }

        $user->is_activated = true;
        $user->save();

        DB::table('user_activations')->where('user_id', $user->id)->delete();

        $token = $user->createToken('token')->accessToken;

        return response()->json([
            "message" => "account activated successfully",
            "token" => $token,
            "user" => $user
        ],200);
    }

    public function resendActivation(ResendActivationCodeFormRequest $request)
    {
        $user = null;
        if ($request->description === User::SIGN_UP_DESC_EMAIL) {
            $user = User::where('email', $request->email)->first();
        }
        if ($request->description === User::SIGN_UP_DESC_PHONE) {
            $user = User::where('phone', $request->phone)->first();
        }

        if (!$user) {
            return response()->json([
                "message" => "The code was not sent, the information is invalid"
            ],417);
        }

        DB::table('user_activations')->where('user_id', $user->id)->delete();

        $token = Str::upper(Str::random(6));
        DB::table('user_activations')->insert(['user_id' => $user->id, 'token' => $token ]);

        $user['token'] = $token;
        if ($user->email) {
            Notification::send($user, new VerifyEmailActivation());
        }

        if ($user->phone) {
            Notification::send($user, new VerifyPhoneActivation($user->phone, $token));
        }

        return response()->json([
            "message" => "The code was sent successfully"
        ]);
    }
}
