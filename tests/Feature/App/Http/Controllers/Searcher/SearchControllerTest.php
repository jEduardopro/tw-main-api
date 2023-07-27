<?php

namespace Tests\Feature\App\Http\Controllers\Searcher;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function a_user_can_search_people()
	{
		$user = User::factory()->activated()->create();
		User::factory()->count(8)->activated()->create();

		$testName = "test name";
		User::factory()->activated()->create(["name" => $testName]);
        Passport::actingAs($user);

		$response = $this->json("GET", "api/search", [
			"q" => "test"
		]);

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($testName, $response->json("data.0.name"));
        $this->assertArrayHasKey("image", $response->json("data.0"));
        $this->assertArrayHasKey("following", $response->json("data.0"));
	}

	/** @test */
	public function a_user_can_search_people_using_the_user_filter()
	{
		$user = User::factory()->activated()->create();
		User::factory()->count(8)->activated()->create();

		$testName = "test name";
		User::factory()->activated()->create(["name" => $testName]);
        Passport::actingAs($user);

		$response = $this->json("GET", "api/search", [
			"q" => "test",
			"f" => "user"
		]);

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($testName, $response->json("data.0.name"));
        $this->assertArrayHasKey("image", $response->json("data.0"));
        $this->assertArrayHasKey("following", $response->json("data.0"));
	}

	/** @test */
	public function a_user_can_search_tweets_with_images()
	{
		Tweet::factory()->count(5)->create();

		$tweet = Tweet::factory()->create(["body" => "fake body of tweet"]);
		$tweet2 = Tweet::factory()->create(["body" => "other example of fake body of tweet"]);

		$collectionName = "images";

		$tweet->addMedia(storage_path('media-test/test_image.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$tweet2->addMedia(storage_path('media-test/image1.jpg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$response = $this->json("GET", "api/search", [
			"q" => "bo image tw fak",
			"f" => "image"
		]);

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$data = $response->json("data.0");
        $this->assertCount(2, $response->json("data"));
		$this->assertArrayHasKey("owner", $data);
		$this->assertArrayHasKey("image", $data["owner"]);
		$this->assertArrayHasKey("body", $data);
		$this->assertArrayHasKey("images", $data);
		$this->assertArrayHasKey("retweets_count", $data);
		$this->assertArrayHasKey("replies_count", $data);
		$this->assertArrayHasKey("likes_count", $data);
	}
}
