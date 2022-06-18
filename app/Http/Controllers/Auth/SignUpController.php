<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignUpFormRequest;
use App\Models\User;

class SignUpController extends Controller
{
    public function signup(SignUpFormRequest $request)
    {
        $user = null;
        if ($request->description == User::SIGN_UP_DESC_EMAIL) {
            $user = User::query()->where('email', $request->email)->first();
        }

        if ($request->description == User::SIGN_UP_DESC_PHONE) {
            $user = User::query()->where('phone_validated', $request->phone)->first();
        }

        if (!$user) {
            return $this->responseWithMessage("sign up fail, your account doesn't exist",403);
        }

        $user->encryptPassword($request->password);
        $user->is_activated = true;
        $user->save();

        $token = $user->createToken('token')->accessToken;

        return $this->responseWithData([
            "message" => "begin onboarding",
            "token" => $token,
            "user" => $user
        ]);
    }
}
