<?php

namespace App\Models;

use App\Services\PhoneNumberValidator;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LocationTrait;

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
        'password',
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
        'phone_verified_at' => 'datetime'
    ];

    /**
     * Determine if the user has verified their phone number.
     *
     * @return bool
     */
    public function hasVerifiedPhone()
    {
        return !is_null($this->phone_verified_at);
    }

    public function verifyPhone()
    {
        $this->phone_verified_at = now();
    }

    public function updatePhoneValidated()
    {
        $phoneNumberValidator = new PhoneNumberValidator();
        $countryCode = $this->getCountryCodeFromLocation();
        $phoneNumberResponse = $phoneNumberValidator->getPhoneNumberValidated($this->phone, $countryCode);
        if ($phoneNumberResponse->success && $phoneNumberResponse->isValid) {
            $this->country_code = $phoneNumberResponse->phoneNumberValidated["countryCode"];
            $this->phone = $phoneNumberResponse->phoneNumberValidated["E164"];
        }
    }

    public function generateUsername(string $name): void
    {
        $this->username = trim(Str::of($name)->slug('_')->lower()) . rand(10, 99);
    }

    public function encryptPassword(string $password): void
    {
        $this->password = Hash::make($password);
    }

}
