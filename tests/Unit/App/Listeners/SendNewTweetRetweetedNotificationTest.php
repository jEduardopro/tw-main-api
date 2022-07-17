<?php

namespace Tests\Unit\App\Listeners;

use App\Events\TweetRetweeted;
use App\Models\CustomDatabaseNotification;
use App\Models\Retweet;
use App\Models\Tweet;
use App\Models\User;
use App\Notifications\NewTweetRetweeted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendNewTweetRetweetedNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notifictions_is_sent_when_a_tweet_is_retweeted()
    {
        Notification::fake();

        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user2->id]);
        $retweet = Retweet::factory()->create(["user_id" => $user->id, "tweet_id" => $tweet->id]);

        TweetRetweeted::dispatch($tweet, $user);

        Notification::assertSentTo($user2, NewTweetRetweeted::class, function ($notification, $channels) use ($user2) {
            $this->assertTrue(!is_null($notification->tweetRetweeted));
            $this->assertTrue(!is_null($notification->retweetOwner));

            $this->assertContains('broadcast', $channels);
            $this->assertNotContains('database', $channels);

            $toArrayResult = $notification->toArray($user2);

            $this->assertArrayHasKey("tweet_retweeted", $toArrayResult);
            $this->assertArrayHasKey("retweet_owner", $toArrayResult);

            $toDatabaseResult = $notification->toDatabase($user2);

            $this->assertArrayHasKey("tweet_retweeted_uuid", $toDatabaseResult);

            $this->assertContains("saveNotification", get_class_methods($notification));
            $this->assertInstanceOf(CustomDatabaseNotification::class, $notification->saveNotification($user2));

            $this->assertInstanceOf(BroadcastMessage::class, $notification->toBroadcast($user2));

            $this->assertEquals("retweet.added", $notification->broadcastType());

            return true;
        });
    }
}
