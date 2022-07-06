<?php

namespace Tests\Unit\App\Models;

use App\Models\Retweet;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RetweetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function retweets_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('retweets', [
                'user_id', 'tweet_id', 'created_at', 'updated_at'
            ])
        );
    }

    /** @test */
    public function a_retweet_model_belongs_to_tweet()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);

        $retweet = Retweet::factory()->create(["user_id" => $user2->id, "tweet_id" => $tweet->id]);

        $this->assertInstanceOf(Tweet::class, $retweet->tweet);
    }


    /** @test */
    public function a_retweet_model_belongs_to_user()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);

        $retweet = Retweet::factory()->create(["user_id" => $user2->id, "tweet_id" => $tweet->id]);

        $this->assertInstanceOf(User::class, $retweet->user);
    }
}
