<?php

namespace Tests\Unit\App\Http\Resources;

use App\Http\Resources\NotificationResource;
use App\Http\Resources\ProfileResource;
use App\Models\CustomDatabaseNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
        $user2 = User::factory()->activated()->create();

        $notification = CustomDatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => "App\Notifications\NewLike",
            "notifiable_type" => User::class,
            "notifiable_id" => $user2->id,
            "senderable_type" => User::class,
            "senderable_id" => $user->id,
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

    /** @test */
    public function a_notification_resources_must_have_the_sender_key_when_its_senderable_relation_is_loaded()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $notification = CustomDatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => "App\Notifications\NewLike",
            "notifiable_type" => User::class,
            "notifiable_id" => $user2->id,
            "senderable_type" => User::class,
            "senderable_id" => $user->id,
            "data" => ["tweet" => "tweet test", "like_sender" => "user test sender"]
        ]);

        $notificationResource = NotificationResource::make($notification)->resolve();

        $this->assertArrayNotHasKey("sender", $notificationResource);

        $notification->load(["senderable" => function(MorphTo $morphTo) {
            $morphTo->morphWith([
                User::class => ["profileImage"]
            ]);
        }]);

        $notificationResource = NotificationResource::make($notification)->resolve();

        $this->assertArrayHasKey("sender", $notificationResource);
        $this->assertInstanceOf(ProfileResource::class, $notificationResource["sender"]);
        $this->assertArrayHasKey("image", $notificationResource["sender"]->resolve());
    }
}
