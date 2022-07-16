<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserNotificationsController extends Controller
{
    public function index(Request $request, $userUuid)
    {
        $user = User::where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the user not found", 404);
        }

        if ($request->user()->id !== $user->id) {
            return $this->responseWithMessage("you do not have permission to perform this action", 403);
        }

        $notifications = $user->notifications()->latest()->paginate();

        return $this->responseWithResource(NotificationResource::collection($notifications));
    }
}
