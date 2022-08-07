<?php

namespace App\Models\Concerns;

use App\Notifications\VerifyEmailActivation;
use App\Notifications\VerifyPhoneActivation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

trait Verificationable
{
	/**
	 * Send notification to user to verify Email Address Or Phone Number
	 */
	public function sendVerificationNotification(string $token): void
	{
		if ($this->email) {
			Notification::send($this, new VerifyEmailActivation($token));
		}

		if ($this->phone) {
			Notification::send($this, new VerifyPhoneActivation($this->phone, $token));
		}
	}

	/**
	 * Generate Verification Code
	 */
	public function createVerificationCodeForUser($userId): string
	{
		$code = $this->generateCode();
		DB::table('user_verifications')->insert(['user_id' => $userId, 'code' => $code, 'created_at' => now()]);

		return $code;
	}

	/**
	 * Find user verification record by code
	 */
	public static function findVerificationByCode(string $code): bool|Object
	{
		$pendingVerification = DB::table('user_verifications')->where('code', $code)->first();

		if (!$pendingVerification) {
			return false;
		}

		return $pendingVerification;
	}

	public static function deleteUserVerificationCodes($userId): void
	{
		DB::table('user_verifications')->where('user_id', $userId)->delete();
	}

	/**
	 * Determine if the user is activated.
	 *
	 * @return bool
	 */
	public function isVerified(): bool
	{
		if (!$this->hasVerifiedPhone() && !$this->hasVerifiedEmail()) {
			return false;
		}

		return true;
	}

	/**
	 * Apply verification to Email or Phone from User
	 *
	 * @return void
	 */
	public function verify(): void
	{
		$verified_at = now();
		if ($this->email) {
			$this->email_verified_at = $verified_at;
		}

		if ($this->phone) {
			$this->phone_verified_at = $verified_at;
		}
	}

	/**
	 * Return the verification date of the user's email or phone
	 */
	public function verificationDate(): bool|string
	{
		if (!$this->hasVerifiedPhone() && !$this->hasVerifiedEmail()) {
			return false;
		}

		if ($this->email_verified_at) {
			return $this->email_verified_at->format('Y-m-d H:i:s');
		}

		return $this->phone_verified_at->format('Y-m-d H:i:s');
	}

	/**
	 * Determine if the user has verified their phone number.
	 *
	 * @return bool
	 */
	public function hasVerifiedPhone(): bool
	{
		return !is_null($this->phone_verified_at);
	}
}
