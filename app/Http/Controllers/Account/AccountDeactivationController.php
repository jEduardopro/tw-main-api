<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountDeactivationController extends Controller
{
    public function deactivate(Request $request)
    {
        $user = $request->user();

        if ($user->isDeactivated()) {
            return $this->responseWithMessage("this account is already deactivated", 403);
        }

        $user->is_activated = false;
        $user->deactivated_at = now();
        $user->save();

        return $this->responseWithData([
            "message" => "account deactivated",
            "deactivated_at" => $user->deactivated_at->format('Y-m-d H:i:s')
        ]);
    }
}
