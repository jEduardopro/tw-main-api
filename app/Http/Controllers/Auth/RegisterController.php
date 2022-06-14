<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFormRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function register(RegisterFormRequest $request)
    {
        if (!$this->existsAtleastEmailOrPhoneField()) {
            return response()->json([
                "message" => "Missing email address or phone number"
            ],422);
        }

        $user = new User();
        $signUpDescription = null;
        $signUpField = [];

        $user->generateUsername($request->name);
        // $user->encryptPassword($request->password);
        $user->name = $request->name;

        if ($request->filled("email")) {
            $user->email = $request->email;
            $signUpField["email"] = $user->email;
            $signUpDescription = User::SIGN_UP_DESC_EMAIL;
        }
        if ($request->filled("phone")) {
            $user->phone = $request->phone;
            $user->updatePhoneValidated();
            $signUpField["phone"] = $user->phone;
            $signUpDescription = User::SIGN_UP_DESC_PHONE;
        }

        $user->save();

        $user["token"] = Str::upper( Str::random(6) );

        UserRegistered::dispatch($user);

        DB::table("user_activations")->insert([ "user_id" => $user->id, "token" => $user["token"] ]);

        $response = array_merge([
            "message" => "begin verification",
            "description" => $signUpDescription
        ], $signUpField);

        return response()->json($response);
    }

    private function existsAtleastEmailOrPhoneField()
    {
        $request = request();
        if (!$request->filled("email") && !$request->filled("phone")) {
            return false;
        }
        $email = $request->email;
        $phone = $request->phone;

        if ($email == "null" && $phone == "null") {
            return false;
        }

        if (empty($email) && empty($phone)) {
            return false;
        }

        return true;
    }
}
