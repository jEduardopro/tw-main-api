<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', 'Auth\RegisterController@register');
Route::post('account/verification', 'Auth\VerificationController@verify');
Route::post('account/verification/resend', 'Auth\VerificationController@resend');
