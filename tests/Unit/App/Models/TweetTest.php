<?php

namespace Tests\Unit\App\Models;

use App\Models\Tweet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tests\TestCase;

class TweetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tweets_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('tweets', [
                'user_id', 'body', 'created_at', 'updated_at', 'deleted_at'
            ])
        );
    }

    /** @test */
    public function a_tweet_model_must_use_the_trait_soft_deletes()
    {
        $this->assertClassUsesTrait(SoftDeletes::class, Tweet::class);
    }

    /** @test */
    public function a_tweet_model_must_use_the_trait_has_factory()
    {
        $this->assertClassUsesTrait(HasFactory::class, Tweet::class);
    }
}
