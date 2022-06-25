<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\{VerificationFormRequest,ResendVerificationCodeFormRequest};
use App\Models\User;
use Carbon\Carbon;

class VerificationController extends Controller
{
    public function verify(VerificationFormRequest $request)
    {
        $userVerification = User::findVerificationByToken($request->token);

        if (!$userVerification) {
            return $this->responseWithErrors([ "token" => ["The token is invalid"] ]);
        }

        $userVerificationDate = Carbon::parse($userVerification->created_at);
        $currentDate = now()->subMinutes(10);
        if ($currentDate->greaterThan($userVerificationDate)) {
            return $this->responseWithErrors([ "token" => ["The token is expired"] ]);
        }

        $user = User::where('id', $userVerification->user_id)->first();

        if (!$user) {
            return $this->responseWithMessage("The account does not exist", 400);
        }

        if ($user->isVerified()) {
            User::deleteUserVerificationTokens($user->id);
            return $this->responseWithMessage("The user account is already verified", 403);
        }

        $user->verify();
        $user->save();

        User::deleteUserVerificationTokens($user->id);

        return $this->responseWithData([
            "message" => "Verification success",
            "verified_at" => $user->verificationDate(),
        ],200);
    }

    public function resend(ResendVerificationCodeFormRequest $request)
    {
        $query = User::query();
        if ($request->description === User::SIGN_UP_DESC_EMAIL) {
            $user = $query->where('email', $request->email)->first();
        }
        if ($request->description === User::SIGN_UP_DESC_PHONE) {
            $user = $query->where('phone', $request->phone)->first();
        }

        if (!$user) {
            return $this->responseWithMessage("The code was not sent, the information is invalid",417);
        }

        User::deleteUserVerificationTokens($user->id);

        $token = $user->createVerificationTokenForUser($user->id);

        $user->sendVerificationNotification($token);

        return $this->responseWithMessage("The code was sent successfully");
    }
}
