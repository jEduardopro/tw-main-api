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
                'country_code', 'phone', 'phone_verified_at',
                'country', 'gender', 'date_birth',
                'is_activated', 'created_at', 'updated_at', 'deleted_at'
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
    public function a_user_has_many_tweets()
    {
        $user = User::factory()->activated()->create();
        Tweet::factory()->create(["user_id" => $user->id]);

        $this->assertInstanceOf(Tweet::class, $user->tweets->first());
    }
}
