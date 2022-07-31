<?php

namespace Tests\Feature\App\Http\Controllers\Searcher;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function a_user_can_search_people()
	{
		User::factory()->count(8)->activated()->create();

		$testName = "test name";
		User::factory()->activated()->create(["name" => $testName]);

		$response = $this->json("GET", "api/search", [
			"q" => "ex"
		]);

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($testName, $response->json("data.0.name"));
	}

	/** @test */
	public function a_user_can_search_people_using_the_user_filter()
	{
		User::factory()->count(8)->activated()->create();

		$testName = "test name";
		User::factory()->activated()->create(["name" => $testName]);

		$response = $this->json("GET", "api/search", [
			"q" => "ex",
			"f" => "user"
		]);

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($testName, $response->json("data.0.name"));
	}

	/** @test */
	public function a_user_can_search_images()
	{
		Tweet::factory()->count(5)->create();

		$tweet = Tweet::factory()->create();

		$collectionName = "images";

		$tweet->addMedia(storage_path('media-test/test_image.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$response = $this->json("GET", "api/search", [
			"q" => "test image",
			"f" => "image"
		]);

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$data = $response->json("data.0");
		$this->assertArrayHasKey("owner", $data);
		$this->assertArrayHasKey("image", $data["owner"]);
		$this->assertArrayHasKey("body", $data);
		$this->assertArrayHasKey("images", $data);
		$this->assertArrayHasKey("retweets_count", $data);
		$this->assertArrayHasKey("replies_count", $data);
		$this->assertArrayHasKey("likes_count", $data);
	}
}
