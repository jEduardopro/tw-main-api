<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use UserAccount;

    public function login(LoginFormRequest $request)
    {
        $userIdentifier = $request->user_identifier;
        $password = $request->password;

        if (!$user = $this->existsUserAccountByIdentifier($userIdentifier)) {
            return $this->responseWithMessage("login fail, we could not find your account", 400);
        }

        $checkPassword = Hash::check($password, $user->password);

        if (!$checkPassword) {
            return $this->responseWithMessage("Wrong password", 400);
        }

        if ($user->isDeactivated()) {
            return $this->responseWithData([
                "message" => "begin account activation process",
                "reactivation_deadline" => $user->accountReactivationDeadline()
            ]);
        }

        $token = $user->createToken('token')->accessToken;


        return $this->responseWithData([
            "token" => $token,
            "user" => UserResource::make($user),
            "message" => "successful login"
        ]);
    }
}
