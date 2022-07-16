<?php

namespace Tests\Unit\App\Http\Resources;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Passport\Passport;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notification_resources_must_have_the_necessary_keys()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $notification = DatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => "App\Notifications\NewLike",
            "notifiable_type" => User::class,
            "notifiable_id" => $user->id,
            "data" => ["tweet" => "tweet test", "like_sender" => "user test sender"]
        ]);

        $notificationResource = NotificationResource::make($notification)->resolve();

        $this->assertArrayHasKey("id", $notificationResource);
        $this->assertArrayHasKey("type", $notificationResource);
        $this->assertArrayHasKey("data", $notificationResource);
        $this->assertArrayHasKey("read_at", $notificationResource);
        $this->assertArrayHasKey("created_at", $notificationResource);

        $this->assertEquals("NewLike", $notificationResource["type"]);
    }
}
