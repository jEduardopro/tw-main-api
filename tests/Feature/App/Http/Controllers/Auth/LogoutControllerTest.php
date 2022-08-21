<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_can_log_out()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $user->createToken('ttoken')->accessToken;

        $response = $this->postJson("api/auth/logout");

        $response->assertSuccessful();

        $this->assertEquals("logout successfully", $response->json("message"));

        $this->assertDatabaseHas("oauth_access_tokens", [
            "user_id" => $user->id,
            "name" => "ttoken",
            "revoked" => 1
        ]);
    }
}
