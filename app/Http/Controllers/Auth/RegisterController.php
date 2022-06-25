<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFormRequest;
use App\Models\User;

class RegisterController extends Controller
{
    public function register(RegisterFormRequest $request)
    {
        if (!$this->existsAtleastEmailOrPhoneField()) {
            return $this->responseWithMessage("Missing email address or phone number",422);
        }

        $user = new User();
        $user->fill($request->validated());
        $signUpDescription = null;
        $signUpField = [];

        $user->generateUsername($request->name);
        $user->setCountryFromIpLocation();

        if ($request->filled("email")) {
            $user->email = $request->email;
            $signUpField["email"] = $user->email;
            $signUpDescription = User::SIGN_UP_DESC_EMAIL;
        }
        if ($request->filled("phone")) {
            $user->setPhoneAndCountryCodeValidated($request->phone);
            $signUpField["phone"] = $user->phone_validated;
            $signUpDescription = User::SIGN_UP_DESC_PHONE;
        }

        $user->save();

        $user["token"] = $user->createVerificationTokenForUser($user->id);

        UserRegistered::dispatch($user);

        $response = array_merge([
            "message" => "begin verification",
            "description" => $signUpDescription
        ], $signUpField);


        return $this->responseWithData($response);
    }

    private function existsAtleastEmailOrPhoneField()
    {
        $request = request();
        if (!$request->filled("email") && !$request->filled("phone")) {
            return false;
        }

        return true;
    }
}
