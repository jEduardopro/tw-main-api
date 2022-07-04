<?php

namespace Tests\Unit\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\LocationTrait;
use App\Models\Concerns\Verificationable;
use App\Models\Tweet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\Concerns\HasUuid;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('users', [
                'uuid', 'name', 'username', 'email', 'email_verified_at',
                'country_code', 'phone', 'phone_verified_at', 'password',
                'country', 'gender', 'date_birth', 'banner_id', 'image_id',
                'description', 'is_activated', 'deactivated_at', 'reactivated_at', 'created_at', 'updated_at', 'deleted_at'
            ])
        );
    }

    /** @test */
    public function a_user_model_must_use_the_trait_soft_deletes()
    {
        $this->assertClassUsesTrait(SoftDeletes::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_has_api_tokens()
    {
        $this->assertClassUsesTrait(HasApiTokens::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_has_factory()
    {
        $this->assertClassUsesTrait(HasFactory::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_notifiable()
    {
        $this->assertClassUsesTrait(Notifiable::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_location_trait()
    {
        $this->assertClassUsesTrait(LocationTrait::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_verificationable()
    {
        $this->assertClassUsesTrait(Verificationable::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_interacts_with_media()
    {
        $this->assertClassUsesTrait(InteractsWithMedia::class, User::class);
    }

    /** @test */
    public function a_user_model_must_use_the_trait_has_uuid()
    {
        $this->assertClassUsesTrait(HasUuid::class, User::class);
    }


    /** @test */
    public function a_user_model_must_use_the_trait_searchable()
    {
        $this->assertClassUsesTrait(Searchable::class, User::class);
    }

    /** @test */
    public function the_get_email_mask_method_must_return_null_if_no_email_set()
    {
        $user = new User();
        $this->assertTrue(is_null( $user->getEmailMask() ) );
    }

    /** @test */
    public function the_get_phone_mask_method_must_return_null_if_no_phone_set()
    {
        $user = new User();
        $this->assertTrue(is_null( $user->getPhoneMask() ) );
    }

    /** @test */
    public function the_verification_date_method_must_return_a_valid_format_date()
    {
        $user = User::factory()->create();
        $this->assertEquals($user->email_verified_at->format('Y-m-d H:i:s'), $user->verificationDate() );

        $user = User::factory()->unverified()->create(["phone_verified_at" => now()]);
        $this->assertEquals($user->phone_verified_at->format('Y-m-d H:i:s'), $user->verificationDate() );
    }

    /** @test */
    public function the_verification_date_method_must_return_false_if_no_email_and_phone_are_verified()
    {
        $user = User::factory()->unverified()->create();
        $this->assertEquals(false, $user->verificationDate() );
    }

    /** @test */
    public function a_user_has_many_tweets()
    {
        $user = User::factory()->activated()->create();
        Tweet::factory()->create(["user_id" => $user->id]);

        $this->assertInstanceOf(Tweet::class, $user->tweets->first());
    }

    /** @test */
    public function a_user_profile_image_belongs_to_the_media()
    {
        $user = User::factory()->activated()->create();

        $collectionName = "profile_image";
        $media = $user->addMedia(storage_path('media-demo/avatar.jpeg'))
                ->preservingOriginal()
                ->toMediaCollection($collectionName);

        $user->image_id = $media->id;
        $user->save();

        $this->assertInstanceOf(Media::class, $user->profileImage);
        $this->assertEquals($media->id, $user->profileImage->id);
    }


    /** @test */
    public function a_user_profile_banner_belongs_to_the_media()
    {
        $user = User::factory()->activated()->create();

        $collectionName = "banner_image";
        $media = $user->addMedia(storage_path('media-demo/bg_banner.jpeg'))
            ->preservingOriginal()
            ->toMediaCollection($collectionName);

        $user->banner_id = $media->id;
        $user->save();

        $this->assertInstanceOf(Media::class, $user->profileBanner);
        $this->assertEquals($media->id, $user->profileBanner->id);
    }

    /** @test */
    public function a_user_has_many_followers()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $user->follow($user2->id);

        $this->assertInstanceOf(User::class, $user2->fresh()->followers->first());
        $this->assertEquals($user->id, $user2->fresh()->followers->first()->id);
    }

    /** @test */
    public function a_user_can_be_following_many_people()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $user->follow($user2->id);

        $this->assertInstanceOf(User::class, $user->fresh()->following->first());
        $this->assertEquals($user2->id, $user->fresh()->following->first()->id);
    }


    /** @test */
    public function a_user_can_stop_following_other_users()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $user->follow($user2->id);

        $this->assertInstanceOf(User::class, $user->fresh()->following->first());
        $this->assertEquals($user2->id, $user->fresh()->following->first()->id);
        $this->assertEquals(1, $user->fresh()->following->count());

        $user->unfollow($user2->id);

        $this->assertEquals(0, $user->fresh()->following->count());
    }
}
