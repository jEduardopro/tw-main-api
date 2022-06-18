<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\AccountController;
use App\Models\User;
use App\Http\Controllers\Auth\Concerns\UserAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test*/
    public function a_user_can_find_your_account_with_their_phone_or_email_or_username()
    {
        $user = User::factory()->withPhoneValidated()->create();

        $response = $this->postJson('api/auth/account/find', ["user_identifier" => $user->email])->assertSuccessful();
        $this->assertEquals("success", $response->json("message"));

        $response = $this->postJson('api/auth/account/find', ["user_identifier" => $user->username])->assertSuccessful();
        $this->assertEquals("success", $response->json("message"));

        $response = $this->postJson('api/auth/account/find', ["user_identifier" => $user->phone])->assertSuccessful();
        $this->assertEquals("success", $response->json("message"));
    }

    /** @test */
    public function the_find_account_process_must_be_fail_if_no_user_account_exists()
    {
        $response = $this->postJson('api/auth/account/find', ["user_identifier" => "invalid_user_identifier"])
                    ->assertStatus(400);

        $this->assertEquals("Sorry, we could not find your account", $response->json("message"));
    }


    /** @test */
    public function the_user_identifier_is_required()
    {
        $this->postJson('api/auth/account/find', ["user_identifier" => null])
            ->assertJsonValidationErrorFor('user_identifier');
    }

    /** @test */
    public function the_account_controller_must_use_the_user_account_trait()
    {
        $this->assertClassUsesTrait(UserAccount::class, AccountController::class);
    }
}
