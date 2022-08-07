<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordFormRequest;
use App\Http\Requests\VerifyResetPasswordFormRequest;
use App\Http\Requests\SendResetPasswordFormRequest;
use App\Models\User;
use App\Notifications\ResetPassword;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ResetPasswordController extends Controller
{
	use UserAccount;

	public function send(SendResetPasswordFormRequest $request)
	{
		$userIdentifier = null;
		if ($request->description == User::RESET_PASSWORD_BY_EMAIL) {
			$userIdentifier = $request->email;
		}

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
		$user = User::query()->where('email', $request->email)->first();

		if (!$user) {
			return $this->responseWithMessage("We could not find your account", 400);
		}

		$user->encryptPassword($request->password);
		$user->save();

		return $this->responseWithMessage("successful password reset");
	}
}
