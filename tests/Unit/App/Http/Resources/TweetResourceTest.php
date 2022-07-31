<?php

namespace Tests\Unit\App\Http\Resources;

use App\Http\Resources\{MediaResource, ProfileResource, TweetResource};
use App\Models\Like;
use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TweetResourceTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function a_tweet_resources_must_have_the_necessary_keys()
	{
		$user = User::factory()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user->id]);

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertTrue(is_string($tweet->uuid));
		$this->assertEquals($tweet->uuid, $tweetResource["id"]);
		$this->assertEquals($tweet->body, $tweetResource["body"]);
		$this->assertEquals($tweet->getReadableCreationDate(), $tweetResource["creation_date_readable"]);
		$this->assertEquals($tweet->created_at, $tweetResource["created_at"]);
	}


	/** @test */
	public function a_tweet_resources_must_have_the_owner_key_when_its_user_relation_is_loaded()
	{
		$user = User::factory()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user->id]);

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayNotHasKey("owner", $tweetResource);

		$tweet->load("user");

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayHasKey("owner", $tweetResource);

		$this->assertInstanceOf(ProfileResource::class, $tweetResource["owner"]);
	}

	/** @test */
	public function a_tweet_resources_must_have_the_images_key_when_its_media_relation_is_loaded()
	{
		$user = User::factory()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user->id]);

		$media = $tweet->addMedia(storage_path('media-test/test_image.jpeg'))
			->preservingOriginal()
			->toMediaCollection("images");

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayNotHasKey("images", $tweetResource);

		$tweet->load("media");

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayHasKey("images", $tweetResource);

		$this->assertEquals(MediaResource::class, $tweetResource["images"]->collects);
	}

	/** @test */
	public function a_tweet_resources_must_have_the_key_of_retweets_count_when_its_retweets_count_relation_is_loaded()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);

		$user->retweet($tweet->id);

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayNotHasKey("retweets_count", $tweetResource);

		$tweet->loadCount('retweets');

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayHasKey("retweets_count", $tweetResource);
	}


	/** @test */
	public function a_tweet_resources_must_have_the_key_of_replies_count_when_its_replies_count_relation_is_loaded()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);
		$tweet2 = Tweet::factory()->create(["user_id" => $user->id]);

		Reply::factory()->create(["tweet_id" => $tweet->id]);

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayNotHasKey("replies_count", $tweetResource);

		$tweet->loadCount('replies');

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayHasKey("replies_count", $tweetResource);
	}


	/** @test */
	public function a_tweet_resources_must_have_the_key_of_likes_count_when_its_likes_count_relation_is_loaded()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);

		Like::factory()->create([
			"user_id" => $user->id,
			"likeable_id" => $tweet->id,
			"likeable_type" => Tweet::class
		]);

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayNotHasKey("likes_count", $tweetResource);

		$tweet->loadCount('likes');

		$tweetResource = TweetResource::make($tweet)->resolve();

		$this->assertArrayHasKey("likes_count", $tweetResource);
	}

	/** @test */
	public function a_tweet_resources_must_have_the_reply_to_key_when_its_reply_and_tweet_relations_are_loaded()
	{
		$user = User::factory()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);
		$reply = Reply::factory()->create(["tweet_id" => $tweet->id]);
		$tweet2 = Tweet::factory()->create(["user_id" => $user->id, "reply_id" => $reply->id]);

		$tweetResource = TweetResource::make($tweet2)->resolve();

		$this->assertArrayNotHasKey("reply_to", $tweetResource);

		$tweet2->load(["reply.tweet" => function ($q) {
			$q->with(["user.profileImage", "media"])
				->withCount(["replies", "retweets", "likes"]);
		}]);

		$tweetResource = TweetResource::make($tweet2)->resolve();

		$this->assertArrayHasKey("reply_to", $tweetResource);
		$this->assertInstanceOf(TweetResource::class, $tweetResource["reply_to"]);
		$tweetReplyResource = $tweetResource["reply_to"]->resolve();
		$this->assertArrayHasKey("owner", $tweetReplyResource);
		$this->assertArrayHasKey("image", $tweetReplyResource["owner"]->resolve());
		$this->assertArrayHasKey("images", $tweetReplyResource);
		$this->assertArrayHasKey("replies_count", $tweetReplyResource);
		$this->assertArrayHasKey("retweets_count", $tweetReplyResource);
		$this->assertArrayHasKey("likes_count", $tweetReplyResource);
	}
}
