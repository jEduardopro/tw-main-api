<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\AccountController;
use App\Models\User;
use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Utils\Task;
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

        $phone = $user->phone;
        $emailParts = explode("@",$user->email);
        $firstPartEmail = substr($emailParts[0], 0, 2). preg_replace("/[A-Za-z0-9._]/", "*", substr($emailParts[0], 2));
        $lastPartEmail = substr($emailParts[1], 0, 1). preg_replace("/[A-Za-z0-9]/", "*", substr($emailParts[1], 1));
        $emailMask = "{$firstPartEmail}@{$lastPartEmail}";
        $phoneMask = preg_replace("/[A-Za-z0-9]/", "*", substr($phone, 0, strlen($phone)-2)) . substr($phone, -2, 2);

        $taskId = Task::PASSWORD_RESET_BEGIN;

        $response = $this->postJson('api/auth/account/find', ["user_identifier" => $user->email, "task_id" => $taskId])->assertSuccessful();
        $this->assertEquals("success", $response->json("message"));

        $response = $this->postJson('api/auth/account/find', ["user_identifier" => $user->username, "task_id" => $taskId])->assertSuccessful();
        $this->assertEquals("success", $response->json("message"));

        $response = $this->postJson('api/auth/account/find', ["user_identifier" => $user->phone, "task_id" => $taskId])->assertSuccessful();
        $this->assertEquals("success", $response->json("message"));

        $this->assertEquals($emailMask, $response->json("account_info.email"));
        $this->assertEquals($phoneMask, $response->json("account_info.phone"));
        $this->assertEquals($user->username, $response->json("account_info.username"));
        $this->assertArrayHasKey("flow_token", $response->json());
        $this->assertDatabaseHas("flows", [
            "name" => $taskId,
            "token" => $response->json("flow_token")
        ]);
    }

    /** @test */
    public function the_find_account_process_must_be_fail_if_no_user_account_exists()
    {
        $response = $this->postJson('api/auth/account/find', ["user_identifier" => "invalid_user_identifier", "task_id" => Task::PASSWORD_RESET_BEGIN])
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
