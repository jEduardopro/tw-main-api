<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
	use RefreshDatabase, WithFaker;

	/** @test */
	public function an_authenticated_user_can_see_your_profile()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$response = $this->getJson("api/profile/{$user->username}");

		$response->assertSuccessful()
			->assertJsonStructure([
				"image",
				"banner",
				"following_count",
				"followers_count",
			]);

		$this->assertEquals($user->uuid, $response->json("id"));
		$this->assertEquals($user->username, $response->json("username"));
	}

	/** @test */
	public function an_authenticated_user_can_see_other_user_profiles()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$user2 = User::factory()->activated()->create();

		$response = $this->getJson("api/profile/{$user2->username}");

		$response->assertSuccessful()
			->assertJsonStructure([
				"image",
				"banner",
				"following_count",
				"followers_count",
			]);

		$this->assertEquals($user2->uuid, $response->json("id"));
		$this->assertEquals($user2->username, $response->json("username"));
	}

	/** @test */
	public function a_guest_user_can_see_your_profile()
	{
		$user = User::factory()->activated()->create();

		$response = $this->getJson("api/profile/{$user->username}");

		$response->assertSuccessful()
			->assertJsonStructure([
				"image",
				"banner",
				"following_count",
				"followers_count",
			]);

		$this->assertEquals($user->uuid, $response->json("id"));
		$this->assertEquals($user->username, $response->json("username"));
	}


	/** @test */
	public function a_guest_user_can_see_other_user_profiles()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();

		$response = $this->getJson("api/profile/{$user2->username}");

		$response->assertSuccessful()
			->assertJsonStructure([
				"image",
				"banner",
				"following_count",
				"followers_count",
			]);

		$this->assertEquals($user2->uuid, $response->json("id"));
		$this->assertEquals($user2->username, $response->json("username"));
	}


	/** @test */
	public function the_profile_request_must_fail_if_user_account_not_found()
	{
		$response = $this->getJson("api/profile/invalid-username");

		$response->assertStatus(400);

		$this->assertEquals("This account doesn't exist", $response->json("message"));
	}


	/** @test */
	public function the_profile_request_must_fail_if_user_account_is_deactivate()
	{
		$user = User::factory()->create();

		$response = $this->getJson("api/profile/{$user->username}");

		$response->assertStatus(400);

		$this->assertEquals("Account suspended", $response->json("message"));
	}

	/** @test */
	public function an_authenticated_user_can_edit_your_profile_banner()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$collectionName = "banner_image";
        $user->addMedia(storage_path('media-test/bg_banner2.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);
		$media = $user->addMedia(storage_path('media-test/bg_banner.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$response = $this->postJson("api/profile/update-banner", ["media_id" => $media->uuid]);

		$response->assertSuccessful();

        $banners = $user->getMedia($collectionName);

        $this->assertEquals(1, $banners->count());
		$this->assertEquals($media->getUrl('medium'), $response->json("profile_banner_url"));
		$this->assertDatabaseHas("users", [
			"id" => $user->id,
			"banner_id" => $media->id,
		]);
	}

	/** @test */
	public function an_authenticated_user_can_edit_your_profile_image()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$collectionName = "profile_image";
        $user->addMedia(storage_path('media-test/image1.jpg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);
		$media = $user->addMedia(storage_path('media-test/avatar.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$response = $this->postJson("api/profile/update-image", ["media_id" => $media->uuid]);

		$response->assertSuccessful();

        $avatars = $user->getMedia($collectionName);

        $this->assertEquals(1, $avatars->count());
		$this->assertEquals($media->getUrl('small'), $response->json("profile_image_url"));
		$this->assertDatabaseHas("users", [
			"id" => $user->id,
			"image_id" => $media->id,
		]);
	}

	/** @test */
	public function the_update_profile_banner_must_fail_if_media_file_no_exists()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$response = $this->postJson("api/profile/update-banner", ["media_id" => "media-id-invalid"]);

		$response->assertStatus(400);
		$this->assertEquals("we could not update the banner, the media file does not exist", $response->json("message"));
	}


	/** @test */
	public function the_update_profile_image_must_fail_if_media_file_no_exists()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$response = $this->postJson("api/profile/update-image", ["media_id" => "media-id-invalid"]);

		$response->assertStatus(400);
		$this->assertEquals("we could not update the image, the media file does not exist", $response->json("message"));
	}

	/** @test */
	public function the_media_id_is_required_for_update_banner_or_image_profile()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->postJson("api/profile/update-banner", ["media_id" => null])
			->assertJsonValidationErrorFor("media_id");

		$this->postJson("api/profile/update-image", ["media_id" => null])
			->assertJsonValidationErrorFor("media_id");
	}


	/** @test */
	public function an_authenticated_user_can_edit_your_profile_info()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$response = $this->putJson("api/profile", $this->profileValidData());

		$response->assertSuccessful()
			->assertJsonStructure(["id", "name", "username", "description"]);

		$data = $response->json();
		$this->assertDatabaseHas("users", [
			"id" => $user->id,
			"name" => $data["name"],
			"description" => $data["description"],
		]);
	}

	/** @test */
	public function the_name_is_required()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->putJson("api/profile", $this->profileValidData(["name" => null]))
			->assertJsonValidationErrorFor("name");
	}

	/** @test */
	public function the_name_must_not_have_more_than_50_characters()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->putJson("api/profile", $this->profileValidData(["name" => "Lorem ipsum dolor sit amet consectetur adipisicing elit. Recusandae."]))
			->assertJsonValidationErrorFor("name");
	}


	/** @test */
	public function the_description_must_not_have_more_than_160_characters()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->putJson("api/profile", $this->profileValidData(["description" => "Lorem ipsum dolor sit amet consectetur adipisicing elit. Odit, odio! Nobis porro reprehenderit, exercitationem repellat sit amet repudiandae recusandae sed veritatis cupiditate."]))
			->assertJsonValidationErrorFor("description");
	}


	/** @test */
	public function the_date_birth_must_be_a_valid_date()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->putJson("api/profile", $this->profileValidData(["date_birth" =>  "1999/06/09"]))
			->assertJsonValidationErrorFor("date_birth");
	}

	/** @test */
	public function the_date_of_birth_must_be_at_least_13_years_old()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$this->putJson('api/profile', $this->userValidData(["date_birth" => now()->format("Y-m-d")]))
			->assertJsonValidationErrorFor('date_birth');
	}

	public function profileValidData($overrides = [])
	{
		return array_merge([
			"name" => $this->faker->name,
			"description" => $this->faker->sentence,
			"date_birth" => now()->subYears(15)->format("Y-m-d")
		], $overrides);
	}
}
