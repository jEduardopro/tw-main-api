<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmailAddressFormRequest;
use App\Http\Requests\UpdateUsernameFormRequest;
use App\Http\Requests\VerifyNewEmailFormRequest;
use App\Models\User;
use App\Notifications\VerifyNewEmailAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AccountInformationController extends Controller
{
    public function updateUsername(UpdateUsernameFormRequest $request)
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

        $code = $user->createVerificationCodeForUser($user->id);

        Notification::send($user, new VerifyNewEmailAddress($email, $code));

        return $this->responseWithMessage("The verification code was sent");
    }


    public function resendNewEmail(VerifyNewEmailFormRequest $request)
    {
        $user = $request->user();
        $email = $request->email;

        User::deleteUserVerificationCodes($user->id);

        $code = $user->createVerificationCodeForUser($user->id);

        Notification::send($user, new VerifyNewEmailAddress($email, $code));

        return $this->responseWithMessage("The verification code was sent");
    }

    public function updateEmail(UpdateEmailAddressFormRequest $request)
    {
        $user = $request->user();
        $email = $request->email;
        $code = $request->code;

        $pendingVerification = User::findVerificationByCode($code);

        if (!$pendingVerification) {
            return $this->responseWithMessage("The code you entered is incorrect", 400);
        }

        $userVerificationDate = Carbon::parse($pendingVerification->created_at);
        $currentDate = now()->subMinutes(10);
        if ($currentDate->greaterThan($userVerificationDate)) {
            return $this->responseWithMessage("The code you entered is expired", 400);
        }

        $user->email = $email;
        $user->email_verified_at = now();
        $user->save();

        User::deleteUserVerificationCodes($user->id);

        return $this->responseWithData([
            "message" => "Email address updated",
            "verified_at" => $user->email_verified_at->format('Y-m-d H:i:s')
        ]);
    }
}
