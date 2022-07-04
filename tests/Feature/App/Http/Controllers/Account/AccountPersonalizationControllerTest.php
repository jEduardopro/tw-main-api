<?php

namespace Tests\Feature\App\Http\Controllers\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AccountPersonalizationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_logged_user_can_update_their_country()
    {

        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $preferenceType = "country";
        $country = $this->faker->country;
        $response = $this->putJson("api/account/personalization", ["preference_type" => $preferenceType, "value" => $country]);

        $response->assertSuccessful();

        $this->assertEquals("{$preferenceType} updated", $response->json("message"));
        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "country" => $country
        ]);
    }

    /** @test */
    public function a_logged_user_can_update_their_gender()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $preferenceType = "gender";
        $gender = $this->faker->randomElement(['male', 'female']);
        $response = $this->putJson("api/account/personalization", ["preference_type" => $preferenceType, "value" => $gender]);

        $response->assertSuccessful();

        $this->assertEquals("{$preferenceType} updated", $response->json("message"));
        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "gender" => $gender
        ]);
    }

    /** @test */
    public function the_preference_type_is_required()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/personalization", ["preference_type" => null, "value" => "test value"])
                ->assertJsonValidationErrorFor("preference_type");
    }


    /** @test */
    public function the_preference_type_must_be_a_valid_preference_type()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/personalization", ["preference_type" => "invalid-preference-type", "value" => "test value"])
                ->assertJsonValidationErrorFor("preference_type");
    }


    /** @test */
    public function the_value_is_required()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/personalization", ["preference_type" => "country", "value" => null])
                ->assertJsonValidationErrorFor("value");
    }


    /** @test */
    public function the_value_is_must_be_string()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/personalization", ["preference_type" => "country", "value" => 78564])
                ->assertJsonValidationErrorFor("value");
    }


    /** @test */
    public function the_value_must_no_have_more_than_80_characters()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/personalization", ["preference_type" => "country", "value" => "Lorem ipsum dolor sit amet consectetur adipisicing elit. Iusto assumenda facilis, sapiente rem reiciendis explicabo" ])
                ->assertJsonValidationErrorFor("value");
    }
}
