<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Verificationable;
use App\Services\PhoneNumberValidator;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;
use Laravel\Scout\Searchable;
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
		'image_id',
		'banner_id',
		'username',
		'email',
		'country_code',
		'phone',
		'phone_validated',
		'password',
		'country',
		'date_birth',
		'gender',
		'description'
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
		'deactivated_at' => 'datetime',
		'reactivated_at' => 'datetime',
		'is_activated' => 'boolean'
	];

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

	/**
	 * Get all of the retweets for the User
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function retweets(): HasMany
	{
		return $this->hasMany(Retweet::class);
	}

	/**
	 * Get the profile image media that owns the User
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function profileImage(): BelongsTo
	{
		return $this->belongsTo(Media::class, 'image_id');
	}


	/**
	 * Get the profile banner media that owns the User
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function profileBanner(): BelongsTo
	{
		return $this->belongsTo(Media::class, 'banner_id');
	}

	/**
	 * The following that belong to the User
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function following(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'followers', 'follower_id', 'followed_id')->withTimestamps();
	}


	/**
	 * The followers that belong to the User
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function followers(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')->withTimestamps();
	}

	/** Scopes */

	public function scopeFindByIdentifier($query, $identifier)
	{
		$query->where('email', $identifier)
			->orWhere('phone', $identifier)
			->orWhere('phone_validated', $identifier)
			->orWhere('username', $identifier);
	}

	public function scopeActive($query)
	{
		$query->where('is_activated', true);
	}

    public function scopeSearch($query, $q)
    {
        $query->where('name', 'like', "%{$q}%")
            ->orWhere('username', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%");
    }


	/** Public methods */

    /**
     * The channels the user receives notification broadcasts on.
     *
     * @return string
     */
    public function receivesBroadcastNotificationsOn()
    {
        return 'users.'.$this->uuid;
    }

	/**
	 * Get the entity's notifications.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function notifications()
	{
		return $this->morphMany(CustomDatabaseNotification::class, 'notifiable')->latest();
	}

	/**
	 * Get the entity's notifications sent.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function notificationsSent()
	{
		return $this->morphMany(CustomDatabaseNotification::class, 'senderable')->latest();
	}



	/**
	 * Create the friendship between follower and followed
	 */
	public function follow(string $userId): void
	{
		$this->following()->attach($userId);
	}


	/**
	 * Delete the friendship between follower and followed
	 */
	public function unfollow(string $userId): void
	{
		$this->following()->detach($userId);
	}

	/**
	 * Retweet a tweet
	 */
	public function retweet($tweetId): void
	{
		$this->retweets()->firstOrCreate(["tweet_id" => $tweetId]);
	}


	/**
	 * Undo Retweet of a tweet
	 */
	public function undoRetweet($tweetId): void
	{
		$this->retweets()->where("tweet_id", $tweetId)->delete();
	}

	public function registerMediaConversions(Media $media = null): void
	{
		$this->addMediaConversion('small')
			->width(150)
			->height(150)
            ->keepOriginalImageFormat();

		$this->addMediaConversion('thumb')
			->width(360)
			->height(360)
            ->keepOriginalImageFormat();

		$this->addMediaConversion('medium')
			->width(680)
			->height(380)
            ->nonQueued()
            ->keepOriginalImageFormat();

		$this->addMediaConversion('large')
			->width(1200)
			->height(675)
            ->keepOriginalImageFormat();
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
	 * Generate Verification Code
	 */
	public function generateCode(): string
	{
		return Str::upper(Str::random(8));
	}

	/**
	 * Returns date of join to twitter clone of a user readable for humans
	 */
	public function getReadableJoinedDate(): string
	{
		$date = $this->created_at;
		return $date->format('F') . " " . $date->format("Y");
	}

	/**
	 * Invalidate all tokens created for this user
	 */
	public function revokeTokensFor($userId)
	{
		Token::where('user_id', $userId)
			->update(['revoked' => true]);
	}

	/**
	 * Returns true if the account is activated
	 */
	public function isActivated(): bool
	{
		return $this->is_activated && $this->reactivated_at && !$this->deactivated_at;
	}

	/**
	 * Returns true if the account is deactivated
	 */
	public function isDeactivated(): bool
	{
		return !$this->is_activated && $this->deactivated_at;
	}

	/**
	 *  Returns the deadline to reactivate the user account
	 */
	public function accountReactivationDeadline(): string
	{
		$reactivationDeadline = $this->deactivated_at->addDays(30);

		return $reactivationDeadline->format('Y-m-d');
	}
}
