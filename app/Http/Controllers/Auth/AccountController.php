<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\FindAccountFormRequest;

class AccountController extends Controller
{
    use UserAccount;

    public function find(FindAccountFormRequest $request)
    {
        $userIdentifier = $request->user_identifier;

        $userAccount = $this->existsUserAccountByIdentifier($userIdentifier);

        if (!$userAccount) {
            return $this->responseWithMessage("Sorry, we could not find your account", 400);
        }

        return $this->responseWithData([
            "account_info" => [
                "username" => $userAccount->username,
                "email" => $userAccount->getEmailMask(),
                "phone" => $userAccount->getPhoneMask()
            ],
            "message" => "success"
        ]);
    }
}
