<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('account/verification', 'Auth\VerificationController@verify');
Route::post('account/verification/resend', 'Auth\VerificationController@resend');
Route::post('auth/register', 'Auth\RegisterController@register');
Route::post('auth/signup', 'Auth\SignUpController@signup');
