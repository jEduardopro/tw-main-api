<?php

use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Tweets\TweetController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function() {
    Route::post('/account/find', 'Auth\AccountController@find');
    Route::post('/login', 'Auth\LoginController@login');

    Route::post('/register', 'Auth\RegisterController@register');
    Route::post('/signup', 'Auth\SignUpController@signup');

    Route::post('/verification/resend', 'Auth\VerificationController@resend');
    Route::post('/verification/verify', 'Auth\VerificationController@verify');

    Route::post('/send-password-reset', 'Auth\ResetPasswordController@send');
    Route::post('/password-verify-code', 'Auth\ResetPasswordController@verify');
    Route::post('/reset-password', 'Auth\ResetPasswordController@reset');
});


Route::group(["middleware" => ["auth:api"]], function() {
    // Tweets
    Route::controller(TweetController::class)->group(function(){
        Route::post("tweets", "store");
        Route::delete("tweets/{id}", "destroy");
    });

    // Media
    Route::controller(MediaController::class)->group(function(){
        Route::post("media/upload", "store");
        Route::delete("media/{id}/remove", "destroy");
    });

    // Users
});

Route::get("users/{username}/profile", "Users\ProfileController@getProfileByUsername");

Route::get("users/{id}/timeline", "Users\UserTimelineController@index");

