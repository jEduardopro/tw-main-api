<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmailAddressFormRequest;
use App\Http\Requests\VerifyNewEmailFormRequest;
use App\Models\User;
use App\Notifications\VerifyNewEmailAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AccountInformationController extends Controller
{
    public function updateUsername(Request $request)
    {
        $user = $request->user();

        $user->username = $request->username;
        $user->save();

        return $this->responseWithMessage("username updated");
    }

    public function verifyNewEmail(VerifyNewEmailFormRequest $request)
    {
        $user = $request->user();
        $email = $request->email;

        $token = $user->createVerificationTokenForUser($user->id);

        Notification::send($user, new VerifyNewEmailAddress($email, $token));

        return $this->responseWithMessage("The verification code was sent");
    }


    public function resendNewEmail(VerifyNewEmailFormRequest $request)
    {
        $user = $request->user();
        $email = $request->email;

        User::deleteUserVerificationTokens($user->id);

        $token = $user->createVerificationTokenForUser($user->id);

        Notification::send($user, new VerifyNewEmailAddress($email, $token));

        return $this->responseWithMessage("The verification code was sent");
    }

    public function updateEmail(UpdateEmailAddressFormRequest $request)
    {
        $user = $request->user();
        $email = $request->email;
        $token = $request->code;

        $pendingVerification = User::findVerificationByToken($token);

        if (!$pendingVerification) {
            return $this->responseWithMessage("The code is invalid", 400);
        }

        $userVerificationDate = Carbon::parse($pendingVerification->created_at);
        $currentDate = now()->subMinutes(10);
        if ($currentDate->greaterThan($userVerificationDate)) {
            return $this->responseWithErrors(["code" => ["The code is expired"]]);
        }

        $user->email = $email;
        $user->email_verified_at = now();
        $user->save();

        User::deleteUserVerificationTokens($user->id);

        return $this->responseWithData([
            "message" => "Email address updated",
            "verified_at" => $user->email_verified_at->format('Y-m-d H:i:s')
        ]);
    }
}
