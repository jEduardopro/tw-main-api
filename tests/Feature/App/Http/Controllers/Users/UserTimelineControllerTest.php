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

        $response = $this->getJson("api/users/{$user->id}/timeline");

        $response->assertSuccessful()
                ->assertJsonStructure(["meta", "links"]);
        $this->assertEquals($tweet2->body, $response->json("data.0.body"));
    }
}
