<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Passport\Passport;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_can_see_their_notifications()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        DatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => "App\Notifications\NewLike",
            "notifiable_type" => User::class,
            "notifiable_id" => $user->id,
            "data" => ["tweet" => "tweet test", "like_sender" => "user test sender"]
        ]);

        $response = $this->getJson("api/users/{$user->uuid}/notifications");

        $response->assertSuccessful()
                ->assertJsonStructure(["data", "meta", "links"]);

    }

    /** @test */
    public function the_user_notifications_must_fail_if_no_user_found()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $response = $this->getJson("api/users/invalid-uuid/notifications");

        $response->assertStatus(404);

        $this->assertEquals("the user not found", $response->json("message"));
    }

    /** @test */
    public function an_authenticated_user_can_not_see_notifications_that_are_not_yours()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        Passport::actingAs($user);

        DatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => "App\Notifications\NewLike",
            "notifiable_type" => User::class,
            "notifiable_id" => $user2->id,
            "data" => ["tweet" => "tweet test", "like_sender" => "user test sender"]
        ]);

        $response = $this->getJson("api/users/{$user2->uuid}/notifications");

        $response->assertStatus(403);

        $this->assertEquals("you do not have permission to perform this action", $response->json("message"));
    }
}
