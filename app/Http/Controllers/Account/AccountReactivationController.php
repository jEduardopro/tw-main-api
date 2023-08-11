<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountReactivationFormRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\Hash;

class AccountReactivationController extends Controller
{
    use UserAccount;

    public function reactivate(AccountReactivationFormRequest $request)
    {
        $userIdentifier = $request->user_identifier;
        $password = $request->password;

        if (!$user = $this->existsUserAccountByIdentifier($userIdentifier)) {
            return $this->responseWithMessage("reactivation fail, we could not find your account", 400);
        }

        if ($user->isActivated()) {
            return $this->responseWithMessage("this account is already activated", 400);
        }

        $checkPassword = Hash::check($password, $user->password);

        if (!$checkPassword) {
            return $this->responseWithMessage("the credentials are invalid", 400);
        }

        $user->is_activated = 1;
        $user->deactivated_at = null;
        $user->reactivated_at = now();
        $user->save();

        $token = $user->createToken('token')->accessToken;
        $user->load(['profileImage']);

        return $this->responseWithData([
            "token" => $token,
            "user" => ProfileResource::make($user),
            "reactivated_at" => $user->reactivated_at->format('Y-m-d H:i:s'),
            "message" => "successfully account reactivation"
        ]);
    }
}
