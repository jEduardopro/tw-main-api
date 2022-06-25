<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserTimelineControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_logged_user_can_see_the_timeline_of_their_tweets()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $tweet1 = Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->subMinutes(5)]);
        $tweet2 = Tweet::factory()->create(["user_id" => $user->id]);

        $response = $this->getJson("api/users/{$user->uuid}/timeline");

        $response->assertSuccessful()
                ->assertJsonStructure(["meta", "links"]);
        $this->assertEquals($tweet2->body, $response->json("data.0.body"));
    }

    /** @test */
    public function the_timeline_of_a_user_must_return_an_error_if_the_user_not_exists()
    {
        $user = User::factory()->withSoftDelete()->create();
        $user2 = User::factory()->activated()->create();
        Passport::actingAs($user2);

        $response = $this->getJson("api/users/{$user->uuid}/timeline");

        $response->assertStatus(400);

        $this->assertEquals("the timeline of tweets is not available for this account", $response->json("message"));
    }
}