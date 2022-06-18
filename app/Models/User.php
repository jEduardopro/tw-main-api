<?php

namespace App\Models;

use App\Models\Concerns\Verificationable;
use App\Services\PhoneNumberValidator;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Verificationable, LocationTrait, SoftDeletes;

    const SIGN_UP_DESC_EMAIL = "signup_with_email";
    const SIGN_UP_DESC_PHONE = "signup_with_phone";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'country_code',
        'phone',
        'phone_validated',
        'password',
        'country'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'is_activated' => 'boolean'
    ];

    public function setPhoneAndCountryCodeValidated($phone): void
    {
        $phoneNumberValidator = new PhoneNumberValidator();
        $countryCode = $this->getCountryCodeFromLocation();
        $phoneNumberResponse = $phoneNumberValidator->getPhoneNumberValidated($phone, $countryCode);
        if ($phoneNumberResponse->success && $phoneNumberResponse->isValid) {
            $this->country_code = $phoneNumberResponse->phoneNumberValidated["countryCode"];
            $this->phone = $phone;
            $this->phone_validated = $phoneNumberResponse->phoneNumberValidated["E164"];
        }
    }

    public function generateUsername(string $name): void
    {
        $this->username = trim(Str::of($name)->slug('_')->lower()) . rand(10, 999);
    }

    public function setCountryFromIpLocation(): void
    {
        $locationData = $this->getLocationData();
        $this->country = $locationData->countryName;
    }

    public function encryptPassword(string $password): void
    {
        $this->password = Hash::make($password);
    }

    /**
     * Return a protected email address of user
     */
    public function getEmailMask(): string|null
    {
        if (!$this->email) {
            return null;
        }
        $emailParts = explode("@", $this->email);
        $firstPartEmail = substr($emailParts[0], 0, 2) . preg_replace("/[A-Za-z0-9._]/", "*", substr($emailParts[0], 2));
        $lastPartEmail = substr($emailParts[1], 0, 1) . preg_replace("/[A-Za-z0-9]/", "*", substr($emailParts[1], 1));
        $emailMask = "{$firstPartEmail}@{$lastPartEmail}";
        return $emailMask;
    }


    /**
     * Return a protected phone number of user
     */
    public function getPhoneMask(): string|null
    {
        $phone = $this->phone;
        if (!$phone) {
            return null;
        }
        $phoneMask = preg_replace("/[A-Za-z0-9]/", "*", substr($phone, 0, strlen($phone) - 2)) . substr($phone, -2, 2);
        return $phoneMask;
    }

    /**
     * Generate Verification Token
     */
    private function generateToken(): string
    {
        return Str::upper(Str::random(6));
    }


    public function scopeFindByIdentifier($query, $identifier)
    {
        if (!$identifier) {
            return;
        }

        $query->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('phone_validated', $identifier)
            ->orWhere('username', $identifier);
    }
}
