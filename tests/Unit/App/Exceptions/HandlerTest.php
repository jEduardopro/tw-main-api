<?php

namespace Tests\Unit\App\Exceptions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_renderable_method_must_response_with_a_custom_message_if_the_url_does_not_exists()
    {
        $response = $this->getJson("api/resource/not-found");

        $response->assertStatus(404);

        $this->assertEquals("the url does not exists", $response->json("message"));
    }
}
