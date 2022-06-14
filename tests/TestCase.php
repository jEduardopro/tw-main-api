<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('passport:install');
    }

    public function userValidData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'name test',
            'email' => 'example_test@example.com',
            'phone' => env("PHONE_NUMBER_TEST")
        ], $overrides);
    }
}
