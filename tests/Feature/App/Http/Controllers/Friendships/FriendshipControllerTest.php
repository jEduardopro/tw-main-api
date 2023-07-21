<?php

namespace Tests\Feature\App\Http\Controllers\Friendships;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FriendshipControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_follow_people()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$user2 = User::factory()->activated()->create();

		$response = $this->postJson("api/friendships/follow", ["user_id" => $user2->uuid]);

		$response->assertSuccessful();
		$this->assertEquals($user2->uuid, $response->json("id"));
		$this->assertEquals($user2->username, $response->json("username"));
	}


	/** @test */
	public function an_authenticated_user_can_unfollow_people()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$user2 = User::factory()->activated()->create();

		$response = $this->deleteJson("api/friendships/unfollow", ["user_id" => $user2->uuid]);

		$response->assertSuccessful();
		$this->assertEquals("You have successfully unfollowed this user", $response->json("message"));
	}

	/** @test */
	public function a_user_cannot_follow_himself()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$response = $this->postJson("api/friendships/follow", ["user_id" => $user->uuid]);

		$response->assertStatus(403);
		$this->assertEquals("You can't follow yourself", $response->json("message"));
	}

	/** @test */
	public function the_friendship_process_must_fail_if_user_not_found()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$followResponse = $this->postJson("api/friendships/follow", ["user_id" => "invalid-user-id"])
			->assertStatus(400);

		$this->assertEquals("the user account does not exist", $followResponse->json("message"));

		$unfollowResponse = $this->deleteJson("api/friendships/unfollow", ["user_id" => "invalid-user-id"])
			->assertStatus(400);

		$this->assertEquals("the user account does not exist", $unfollowResponse->json("message"));
	}

	/** @test */
	public function a_user_can_only_follow_another_user_once()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$user2 = User::factory()->activated()->create();

		$user->follow($user2->id);

		$response = $this->postJson("api/friendships/follow", ["user_id" => $user2->uuid])
			->assertStatus(400)
			->assertJsonStructure(["message"]);

		$this->assertEquals("you are already following this user", $response->json("message"));
	}

	/** @test */
	public function the_user_id_is_required()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->postJson("api/friendships/follow", ["user_id" => null])
			->assertJsonValidationErrorFor("user_id");
	}
}
