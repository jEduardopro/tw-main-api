<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function userValidData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'name test',
            'email' => 'example_test@example.com',
            'phone' => env("PHONE_NUMBER_TEST")
        ], $overrides);
    }
}
