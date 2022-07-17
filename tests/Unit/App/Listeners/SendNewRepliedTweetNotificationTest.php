<?php

namespace Tests\Unit\App\Listeners;

use App\Events\RepliedTweet;
use App\Models\CustomDatabaseNotification;
use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use App\Notifications\NewRepliedTweet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendNewRepliedTweetNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notifictions_is_sent_when_a_tweet_is_replied()
    {
        Notification::fake();

        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);
        $reply = Reply::factory()->create(["tweet_id" => $tweet->id]);
        $replyTweet = Tweet::factory()->create(["user_id" => $user2->id, "reply_id" => $reply->id]);

        RepliedTweet::dispatch($replyTweet, $user);

        Notification::assertSentTo($user, NewRepliedTweet::class, function($notification, $channels) use ($user) {
            $this->assertTrue(!is_null($notification->replyTweet));
            $this->assertTrue(!is_null($notification->userReplying));

            $this->assertContains('broadcast', $channels);
            $this->assertNotContains('database', $channels);

            $toArrayResult = $notification->toArray($user);

            $this->assertArrayHasKey("reply_tweet", $toArrayResult);
            $this->assertArrayHasKey("owner", $toArrayResult["reply_tweet"]);
            $this->assertArrayHasKey("image", $toArrayResult["reply_tweet"]["owner"]->resolve());
            $this->assertArrayHasKey("user_replying", $toArrayResult);

            $toDatabaseResult = $notification->toDatabase($user);

            $this->assertArrayHasKey("reply_tweet_uuid", $toDatabaseResult);
            $this->assertArrayHasKey("reply_tweet", $toDatabaseResult);
            $this->assertArrayHasKey("user_replying", $toDatabaseResult);

            $this->assertContains("saveNotification", get_class_methods($notification));
            $this->assertInstanceOf(CustomDatabaseNotification::class, $notification->saveNotification($user));

            $this->assertInstanceOf(BroadcastMessage::class, $notification->toBroadcast($user));

            $this->assertEquals("reply.added", $notification->broadcastType());

            return true;
        });
    }
}
