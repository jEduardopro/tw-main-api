<?php

use App\Http\Controllers\Account\AccountInformationController;
use App\Http\Controllers\Friendships\FriendshipController;
use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Replies\RepliesController;
use App\Http\Controllers\Retweets\RetweetsController;
use App\Http\Controllers\Tweets\TweetController;
use App\Http\Controllers\Tweets\TweetLikesController;
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
    // Home Timeline
    Route::get("home/timeline", "Home\HomeTimelineController@index");

    // Tweets
    Route::controller(TweetController::class)->prefix("tweets")->group(function(){
        Route::post("/", "store");
        Route::delete("/{id}", "destroy");
    });

    // Replies
    Route::controller(RepliesController::class)->prefix("replies")->group(function(){
        Route::post("/", "store");
        Route::delete("/{id}", "destroy");
    });

    // Retweets
    Route::controller(RetweetsController::class)->prefix("retweets")->group(function(){
        Route::post("/", "store");
        Route::delete("/{id}", "destroy");
    });

    // Owners Retweets of a Tweet
    Route::get("tweets/{id}/owners-retweets", "Tweets\TweetOwnersRetweetsController@index");


    // Tweet Likes
    Route::controller(TweetLikesController::class)->prefix("tweets")->group(function () {
        Route::post("/{id}/likes","store");
        Route::delete("/{id}/likes", "destroy");
    });

    // Media
    Route::controller(MediaController::class)->prefix("media")->group(function(){
        Route::post("/upload", "store");
        Route::delete("/{id}/remove", "destroy");
    });

    // User Followers and Followings
    Route::prefix("users")->group(function(){
        Route::get("/{id}/followers", "Users\UserFollowersController@index");
        Route::get("/{id}/followings", "Users\UserFollowingsController@index");
    });

    // Profile
    Route::controller(ProfileController::class)->prefix("profile")->group(function () {
        Route::put("/", "update");
        Route::post("/update-banner", "updateBanner");
        Route::post("/update-image", "updateImage");
    });

    // Friendships
    Route::controller(FriendshipController::class)->prefix("friendships")->group(function () {
        Route::post("/follow", "follow");
        Route::delete("/unfollow", "unfollow");
    });

    // Account
    Route::prefix("account")->group(function () {
        Route::put("/personalization", "Account\AccountPersonalizationController@update");
        // Account Password
        Route::put("/password", "Account\AccountPasswordController@update");
        // Account Deactivation
        Route::post("/deactivation", "Account\AccountDeactivationController@deactivate");
    });

    // Account information
    Route::controller(AccountInformationController::class)->prefix("account/information")->group(function () {
        Route::put("/update-username", "updateUsername");
        Route::post("/verify-new-email", "verifyNewEmail");
        Route::post("/resend-new-email", "resendNewEmail");
        Route::put("/update-email", "updateEmail");
    });
});

// Show Tweet
Route::get("tweets/{id}", "Tweets\TweetController@show");
// Show Tweet Replies
Route::get("tweets/{id}/replies", "Tweets\TweetRepliesController@index");

Route::get("profile/{username}", "Users\ProfileController@getProfileByUsername");

// User Profile Timeline
Route::get("users/{id}/timeline", "Users\UserTimelineController@index");
// User Profile Tweets And Replies Timeline
Route::get("users/{id}/tweets-replies-timeline", "Users\UserTweetsAndRepliesTimelineController@index");

// Searcher
Route::get("search", "Searcher\SearchController@index");

// Account Reactivation
Route::post("account/reactivation", "Account\AccountReactivationController@reactivate");

