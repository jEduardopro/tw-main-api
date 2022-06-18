<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;

trait UserAccount
{
    public function existsUserAccountByIdentifier(string $identifier): bool|User
    {
        $user = User::query()->findByIdentifier($identifier)->first();

        if (!$user) {
            return false;
        }

        return $user;
    }
}
