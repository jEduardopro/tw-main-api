<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use UserAccount;

    public function login(LoginFormRequest $request)
    {
        $userIdentifier = $request->user_identifier;
        $password = $request->password;

        if (Auth::check()) {
            $this->responseWithMessage("you are already logged in");
        }

        if (!$user = $this->existsUserAccountByIdentifier($userIdentifier)) {
            return $this->responseWithMessage("login fail, we could not find your account", 400);
        }

        $checkPassword = Hash::check($password, $user->password);

        $token = $user->createToken('token')->accessToken;

        if (!$checkPassword) {
            return $this->responseWithMessage("Wrong password", 400);
        }

        return $this->responseWithData([
            "token" => $token,
            "user" => $user,
            "message" => "successful login"
        ]);
    }
}
