<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordFormRequest;
use App\Http\Requests\VerifyResetPasswordFormRequest;
use App\Http\Requests\SendResetPasswordFormRequest;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Traits\FlowTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ResetPasswordController extends Controller
{
	use UserAccount, FlowTrait;

	public function send(SendResetPasswordFormRequest $request)
	{
		$userIdentifier = null;
        $description = $request->description;
        $flow_token = $request->flow_token;

        if (!$flow = $this->findFlowByToken($flow_token)) {
            return $this->responseWithMessage("This flow was not found.", 400);
        }
        if ($this->flowIsExpired($flow)) {
            $flow->delete();
            return $this->responseWithMessage("This flow has expired, please start again.", 400);
        }
        $payloadFlow = $flow->payload;
        $userIdentifier = $payloadFlow[$description];

		if (!$user = $this->existsUserAccountByIdentifier($userIdentifier)) {
			return $this->responseWithMessage("We could not find your account", 400);
		}

		$token = $user->generateCode();
		DB::table('password_resets')->where("email", $user->email)->delete();
		DB::table('password_resets')->insert(["email" => $user->email, "token" => $token, "created_at" => now()]);

		Notification::send($user, new ResetPassword($token));

		return $this->responseWithMessage("The verification code was sent");
	}

	public function verify(VerifyResetPasswordFormRequest $request)
	{
		$token = $request->token;
		$passwordReset = DB::table('password_resets')->where("token", $token)->first();

		if (!$passwordReset) {
			return $this->responseWithMessage("The code you entered is incorrect", 400);
		}

		$passwordResetDate = Carbon::parse($passwordReset->created_at);
		$currentDate = now()->subMinutes(10);
		if ($currentDate->greaterThan($passwordResetDate)) {
			return $this->responseWithMessage("The code you entered is expired", 400);
		}

		DB::table('password_resets')->where("email", $passwordReset->email)->delete();

		return $this->responseWithMessage("password reset request verified successfully");
	}

	public function reset(ResetPasswordFormRequest $request)
	{
        $flow_token = $request->flow_token;

        if (!$flow = $this->findFlowByToken($flow_token)) {
            return $this->responseWithMessage("This flow was not found.", 400);
        }
        if ($this->flowIsExpired($flow)) {
            $flow->delete();
            return $this->responseWithMessage("This flow has expired, please start again.", 400);
        }

        $payload = $flow->payload;
        $username = $payload["username"];

		$user = User::query()->where('username', $username)->first();

		if (!$user) {
			return $this->responseWithMessage("We could not find your account", 400);
		}

		$user->encryptPassword($request->password);
		$user->save();

        $flow->delete();

		return $this->responseWithMessage("successful password reset");
	}
}
