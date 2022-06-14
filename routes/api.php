<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', 'Auth\RegisterController@register');
Route::post('account/activation', 'Auth\AccountActivationController@activateAccount');
Route::post('account/activation/resend', 'Auth\AccountActivationController@resendActivation');
