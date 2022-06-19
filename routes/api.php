<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('auth/account/find', 'Auth\AccountController@find');
Route::post('auth/login', 'Auth\LoginController@login');

Route::post('auth/register', 'Auth\RegisterController@register');
Route::post('auth/signup', 'Auth\SignUpController@signup');

Route::post('auth/verification/resend', 'Auth\VerificationController@resend');
Route::post('auth/verification/verify', 'Auth\VerificationController@verify');

Route::post('auth/send-password-reset', 'Auth\ResetPasswordController@send');
Route::post('auth/password-verify-code', 'Auth\ResetPasswordController@verify');
Route::post('auth/reset-password', 'Auth\ResetPasswordController@reset');


Route::group(["middleware" => ["auth:api"]], function() {
    // Tweets
    Route::post("tweets", "Tweets\TweetController@store");
    Route::delete("tweets/{id}", "Tweets\TweetController@destroy");

    // Media
    Route::post("media/upload", "Media\MediaController@store");
});


