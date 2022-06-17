<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFormRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function register(RegisterFormRequest $request)
    {
        if (!$this->existsAtleastEmailOrPhoneField()) {
            return $this->responseWithMessage("Missing email address or phone number",422);
        }

        $user = new User();
        $signUpDescription = null;
        $signUpField = [];

        $user->generateUsername($request->name);
        $user->name = $request->name;
        $user->setCountryFromIpLocation();
        $user->date_birth = $request->date_birth;

        if ($request->filled("email")) {
            $user->email = $request->email;
            $signUpField["email"] = $user->email;
            $signUpDescription = User::SIGN_UP_DESC_EMAIL;
        }
        if ($request->filled("phone")) {
            $user->phone = $request->phone;
            $user->updatePhoneValidated();
            $signUpField["phone"] = $user->phone;
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
        $email = $request->email;
        $phone = $request->phone;

        if ($email == "null" && $phone == "null") {
            return false;
        }

        if (empty($email) && empty($phone)) {
            return false;
        }

        return true;
    }
}
