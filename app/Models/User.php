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

    public function updatePhoneValidated(): void
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
     * Generate Verification Token
     */
    private function generateToken(): string
    {
        return Str::upper(Str::random(6));
    }

}
