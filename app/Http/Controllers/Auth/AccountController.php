<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\FindAccountFormRequest;
use App\Models\Flow;
use App\Traits\FlowTrait;

class AccountController extends Controller
{
    use UserAccount, FlowTrait;

    public function find(FindAccountFormRequest $request)
    {
        $userIdentifier = $request->user_identifier;
        $task = $request->task_id;

        $userAccount = $this->existsUserAccountByIdentifier($userIdentifier);

        if (!$userAccount) {
            return $this->responseWithMessage("Sorry, we could not find your account", 400);
        }

        $flow = $this->createNewFlow($task, $this->getFlowPayload($userAccount));

        return $this->responseWithData([
            "account_info" => [
                "username" => $userAccount->username,
                "email" => $userAccount->getEmailMask(),
                "phone" => $userAccount->getPhoneMask()
            ],
            "message" => "success",
            "flow_token" => $flow->token
        ]);
    }

    private function getFlowPayload($accountInfo): array
    {
        return [
            "username" => $accountInfo->username,
            "reset_password_by_email" => $accountInfo->email,
            "reset_password_by_phone" => $accountInfo->phone,
        ];
    }
}
