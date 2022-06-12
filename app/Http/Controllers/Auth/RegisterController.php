<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $user = new User();

        $user->generateUsername($request->name);
        // $user->encryptPassword($request->password);
        $user->name = $request->name;

        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        $user->save();

        $user['token'] = Str::upper( Str::random(6) );

        UserRegistered::dispatch($user);

        DB::table('user_activations')->insert([ 'user_id' => $user->id, 'token' => $user['token'] ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone
            ]
        ]);
    }
}
