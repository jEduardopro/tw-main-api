<?php

namespace Tests\Feature\App\Http\Controllers\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AccountDeactivationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_logged_user_can_deactivate_their_account()
    {
        $user = User::factory()->activated()->create();

        Passport::actingAs($user);

        $response = $this->postJson("api/account/deactivation");

        $response->assertSuccessful()
                ->assertJsonStructure(["deactivated_at"]);

        $this->assertEquals("account deactivated", $response->json("message"));

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "is_activated" => 0,
            "deactivated_at" => $response->json("deactivated_at")
        ]);
    }

    /** @test */
    public function a_user_account_cannot_be_deactivated_if_it_is_already_deactivated()
    {
        $user = User::factory()->deactivated()->create();

        Passport::actingAs($user);

        $response = $this->postJson("api/account/deactivation");

        $response->assertStatus(403);

        $this->assertEquals("this account is already deactivated", $response->json("message"));
    }
}
