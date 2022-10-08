<?php

namespace Tests\Unit\App\Listeners;

use App\Events\ModelLiked;
use App\Models\CustomDatabaseNotification;
use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use App\Notifications\NewLike;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SendNewLikeNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notification_is_sent_when_a_tweet_is_liked()
    {
        Notification::fake();

        $user = User::factory()->activated()->create();
        $tweetOwner = User::factory()->activated()->create();
        Passport::actingAs($user);

        $tweet = Tweet::factory()->create(["user_id" => $tweetOwner->id]);

        $tweet->likes()->create(["user_id" => $user->id]);

        ModelLiked::dispatch($tweet, $user);

        Notification::assertSentTo($tweetOwner, NewLike::class, function($notification, $channels) use ($tweet, $tweetOwner) {
            $this->assertTrue(!is_null($notification->tweet));
            $this->assertTrue(!is_null($notification->likeSender));

            $this->assertContains('broadcast', $channels);
            $this->assertNotContains('database', $channels);
            $toArrayResult = $notification->toArray($tweetOwner);
            $this->assertArrayHasKey("tweet", $toArrayResult);
            $this->assertTweetResourceData($toArrayResult["tweet"]);
            $this->assertArrayHasKey("like_sender", $toArrayResult);
            $this->assertArrayHasKey("image", $toArrayResult["like_sender"]);

            $toDatabaseResult = $notification->toDatabase($tweetOwner);
            $this->assertArrayHasKey("tweet_uuid", $toDatabaseResult);
            $this->assertArrayHasKey("tweet", $toDatabaseResult);


            $this->assertContains("saveNotification", get_class_methods($notification));
            $this->assertInstanceOf(CustomDatabaseNotification::class, $notification->saveNotification($tweetOwner));

            $this->assertInstanceOf(BroadcastMessage::class, $notification->toBroadcast($tweetOwner));

            return true;
        });

    }

    private function assertTweetResourceData(array $data)
	{
		$this->assertArrayHasKey("id", $data);
		$this->assertArrayHasKey("body", $data);
		$this->assertArrayHasKey("owner", $data);
		$this->assertArrayHasKey("mentions", $data);
		$this->assertArrayHasKey("images", $data);
		$this->assertArrayHasKey("replies_count", $data);
		$this->assertArrayHasKey("retweets_count", $data);
		$this->assertArrayHasKey("likes_count", $data);
	}
}
