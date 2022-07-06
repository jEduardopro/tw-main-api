<?php

namespace Tests\Unit\App\Models;

use App\Models\Tweet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Likeable;
use App\Models\User;
use Tests\TestCase;

class TweetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tweets_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('tweets', [
               'uuid', 'user_id', 'body', 'created_at', 'updated_at', 'deleted_at'
            ])
        );
    }

    /** @test */
    public function a_tweet_model_must_use_the_trait_soft_deletes()
    {
        $this->assertClassUsesTrait(SoftDeletes::class, Tweet::class);
    }

    /** @test */
    public function a_tweet_model_must_use_the_trait_has_factory()
    {
        $this->assertClassUsesTrait(HasFactory::class, Tweet::class);
    }

    /** @test */
    public function a_tweet_model_must_use_the_trait_interacts_with_media()
    {
        $this->assertClassUsesTrait(InteractsWithMedia::class, Tweet::class);
    }

    /** @test */
    public function a_tweet_model_must_use_the_trait_has_uuid()
    {
        $this->assertClassUsesTrait(HasUuid::class, Tweet::class);
    }


    /** @test */
    public function a_tweet_model_must_use_the_trait_likeable()
    {
        $this->assertClassUsesTrait(Likeable::class, Tweet::class);
    }


    /** @test */
    public function a_tweet_model_belongs_to_user()
    {
        $user = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);

        $this->assertInstanceOf(User::class, $tweet->user);
    }


    /** @test */
    public function a_tweet_model_has_many_retweets()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);

        $tweet->retweets()->create(["user_id" => $user2->id]);

        $this->assertInstanceOf(Tweet::class, $tweet->retweets->first()->tweet);
    }


    /** @test */
    public function a_tweet_model_has_many_replies()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);
        $tweet2 = Tweet::factory()->create(["user_id" => $user2->id]);

        $tweet->replies()->create(["reply_tweet_id" => $tweet2->id]);

        $this->assertInstanceOf(Tweet::class, $tweet->replies->first()->tweet);
    }
}
