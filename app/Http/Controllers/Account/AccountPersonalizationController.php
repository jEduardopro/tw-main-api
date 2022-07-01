<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountPersonalizationFormRequest;
use Illuminate\Http\Request;

class AccountPersonalizationController extends Controller
{
    public function update(AccountPersonalizationFormRequest $request)
    {
        $user = $request->user();
        $preferenceType = $request->preference_type;

        $user->{$preferenceType} = $request->value;
        $user->save();

        return $this->responseWithMessage("{$preferenceType} updated");
    }
}
