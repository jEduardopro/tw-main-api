<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserFollowingsControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_list_the_people_they_follow()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$user3 = User::factory()->activated()->create();
		Passport::actingAs($user);

		$user->follow($user2->id);

		DB::table('followers')->insert([
			"follower_id" => $user->id,
			"followed_id" => $user3->id,
			"created_at" => now()->addDay(),
			"updated_at" => now()->addDay(),
		]);

		$response = $this->getJson("api/users/{$user->uuid}/followings");

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($user3->uuid, $response->json("data.0.id"));
		$this->assertArrayHasKey("image", $response->json("data.0"));
		$this->assertArrayHasKey("following", $response->json("data.0"));
	}

	/** @test */
	public function the_followings_index_method_must_fail_if_not_user_found()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$response = $this->getJson("api/users/invalid-user-id/followings")
			->assertStatus(400);

		$this->assertEquals("the followings list is not available for this user account", $response->json("message"));
	}

	/** @test */
	public function user_followings_index_cache()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		Passport::actingAs($user);

		$user->follow($user2->id);
		$followingsPaginated = $user->following()->paginate();

		Cache::shouldReceive('remember')
			->once()
			->with("user_{$user->id}_followings_list", 900, \Closure::class)
			->andReturn($followingsPaginated);

		$response = $this->getJson("api/users/{$user->uuid}/followings");

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($user2->uuid, $response->json("data.0.id"));
	}
}
