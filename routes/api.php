<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('auth/account/find', 'Auth\AccountController@find');
Route::post('auth/login', 'Auth\LoginController@login');

Route::post('auth/register', 'Auth\RegisterController@register');
Route::post('auth/signup', 'Auth\SignUpController@signup');

Route::post('auth/verification/resend', 'Auth\VerificationController@resend');
Route::post('auth/verification/verify', 'Auth\VerificationController@verify');
