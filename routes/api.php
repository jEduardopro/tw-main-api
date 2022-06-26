<?php

use App\Http\Controllers\Friendships\FriendshipController;
use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Tweets\TweetController;
use App\Http\Controllers\Users\ProfileController;
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
    Route::controller(TweetController::class)->prefix("tweets")->group(function(){
        Route::post("/", "store");
        Route::delete("/{id}", "destroy");
    });

    // Media
    Route::controller(MediaController::class)->prefix("media")->group(function(){
        Route::post("/upload", "store");
        Route::delete("/{id}/remove", "destroy");
    });

    // Users

    // Profile
    Route::controller(ProfileController::class)->prefix("profile")->group(function () {
        Route::put("/", "update");
        Route::post("/update-banner", "updateBanner");
        Route::post("/update-image", "updateImage");
    });

    //Friendships
    Route::controller(FriendshipController::class)->prefix("friendships")->group(function () {
        Route::post("/follow", "follow");
        Route::delete("/unfollow", "unfollow");
    });
});

Route::get("profile/{username}", "Users\ProfileController@getProfileByUsername");

Route::get("users/{id}/timeline", "Users\UserTimelineController@index");

