<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use App\Traits\FlowTrait;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use UserAccount, FlowTrait;

    public function login(LoginFormRequest $request)
    {
        $userIdentifier = $request->user_identifier;
        $password = $request->password;
        $flow_token = $request->flow_token;

        if (!$flow = $this->findFlowByToken($flow_token)) {
            return $this->responseWithMessage("This flow was not found.", 400);
        }
        if ($this->flowIsExpired($flow)) {
            $flow->delete();
            return $this->responseWithMessage("This flow has expired, please start again.", 400);
        }

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

        $flow->delete();
        $user->load(['profileImage']);

        return $this->responseWithData([
            "token" => $token,
            "user" => ProfileResource::make($user),
            "message" => "successful login"
        ]);
    }
}
