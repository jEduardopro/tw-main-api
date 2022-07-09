<?php

namespace Tests\Unit\App\Models;

use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function replies_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('replies', [
                'tweet_id', 'created_at', 'updated_at'
            ])
        );
    }

    /** @test */
    public function a_reply_model_belongs_to_tweet()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user2->id]);
        $tweet2 = Tweet::factory()->create(["user_id" => $user->id]);

        $reply = Reply::factory()->create(["tweet_id" => $tweet->id]);

        $this->assertInstanceOf(Tweet::class, $reply->tweet);
    }


    /** @test */
    public function a_reply_model_has_one_tweet_reply()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user2->id]);
        $tweet2 = Tweet::factory()->create(["user_id" => $user->id]);

        $reply = Reply::factory()->create(["tweet_id" => $tweet->id]);
        $tweet2->reply_id = $reply->id;
        $tweet2->save();

        $this->assertInstanceOf(Tweet::class, $reply->tweetReply);
    }
}
