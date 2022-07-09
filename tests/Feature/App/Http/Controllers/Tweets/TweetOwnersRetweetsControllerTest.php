<?php

namespace Tests\Feature\App\Http\Controllers\Tweets;

use App\Models\Retweet;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TweetOwnersRetweetsControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_can_see_owners_retweets_of_a_tweet()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        Passport::actingAs($user);
        $tweet = Tweet::factory()->create(["user_id" => $user2->id]);

        Retweet::factory()->count(20)->create(["tweet_id" => $tweet->id]);

        $response = $this->getJson("api/tweets/{$tweet->uuid}/owners-retweets")->assertSuccessful()
                ->assertJsonStructure(["data", "meta", "links"]);

        $data = $response->json("data.0");
        $this->assertArrayHasKey("image", $data);
    }
}
