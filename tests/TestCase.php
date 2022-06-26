<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;

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
            'phone' => env("PHONE_NUMBER_TEST"),
            'date_birth' => now()->subYears(13)->format('Y-m-d')
        ], $overrides);
    }

    protected function assertClassUsesTrait($trait, $class)
    {
        $this->assertArrayHasKey(
            $trait,
            class_uses($class),
            "{$class} must use {$trait} trait"
        );
    }
}
