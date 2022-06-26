<?php

namespace Tests\Feature\App\Http\Controllers\Followers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FriendshipControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_logged_user_can_follow_people()
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
    public function a_logged_user_can_unfollow_people()
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
}
