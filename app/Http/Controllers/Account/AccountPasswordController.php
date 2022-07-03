<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountPasswordController extends Controller
{
    public function update(UpdatePasswordFormRequest $request)
    {
        $user = $request->user();

        $currentPassword = $request->current_password;
        $newPassword = $request->new_password;

        $checkCurrentPassword = Hash::check($currentPassword, $user->password);
        if (!$checkCurrentPassword) {
            return $this->responseWithErrors(["current_password" => ["The password you entered is incorrect"]]);
        }

        $user->encryptPassword($newPassword);
        $user->save();

        $user->revokeTokensFor($user->id);

        return $this->responseWithMessage("password updated");
    }
}
