<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Verificationable;
use App\Services\PhoneNumberValidator;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, Verificationable, LocationTrait, SoftDeletes, InteractsWithMedia, HasUuid;

    const SIGN_UP_DESC_EMAIL = "signup_with_email";
    const SIGN_UP_DESC_PHONE = "signup_with_phone";
    const RESET_PASSWORD_BY_EMAIL = "reset_password_by_email";

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
        'country',
        'date_birth'
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

    // protected static function booted()
    // {
    //     // dd('dkfhgjkjdfg');
    //     User::creating(function ($model) {
    //         dd('djkhjdkd');
    //         $model->uuid = Str::uuid()->toString();
    //     });
    // }

    /** Relationships */

    /**
     * Get all of the tweets for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tweets(): HasMany
    {
        return $this->hasMany(Tweet::class);
    }


    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('small')
            ->width(150)
            ->height(150);

        $this->addMediaConversion('thumb')
            ->width(360)
            ->height(360);

        $this->addMediaConversion('medium')
            ->width(680)
            ->height(380);

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(675);
    }

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
    public function generateToken(): string
    {
        return Str::upper(Str::random(8));
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
