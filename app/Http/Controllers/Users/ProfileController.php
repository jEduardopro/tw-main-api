<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileFormRequest;
use App\Http\Requests\UpdateProfileMediaFormRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;

class ProfileController extends Controller
{
    public function getProfileByUsername($username)
    {
        $user = User::where('username', $username)
                ->with(["profileImage", "profileBanner"])
                ->withCount(["following", "followers"])->first();

        if (!$user) {
            return $this->responseWithMessage("This account doesn't exist", 400);
        }

        if (!$user->is_activated) {
            return $this->responseWithMessage("Account suspended", 400);
        }


        return $this->responseWithResource(ProfileResource::make($user));
    }

    public function update(UpdateProfileFormRequest $request)
    {
        $user = $request->user();
        $user->fill($request->all());
        $user->save();

        return $this->responseWithResource(ProfileResource::make($user));
    }

    public function updateBanner(UpdateProfileMediaFormRequest $request)
    {
        $user = $request->user();
        $mediaUuid = $request->media_id;

        $media = $user->media()->where("uuid", $mediaUuid)->first();
        if (!$media) {
            return $this->responseWithMessage("we could not update the banner, the media file does not exist",400);
        }

        $user->media()->whereNotIn("uuid", [$mediaUuid])->where("collection_name", "banner_image")->delete();

        $user->banner_id = $media->id;
        $user->save();

        return $this->responseWithData([
            "profile_banner_url" => $media->getUrl('medium')
        ]);
    }


    public function updateImage(UpdateProfileMediaFormRequest $request)
    {
        $user = $request->user();
        $mediaUuid = $request->media_id;

        $media = $user->media()->where("uuid", $mediaUuid)->first();
        if (!$media) {
            return $this->responseWithMessage("we could not update the image, the media file does not exist", 400);
        }

        $user->media()->whereNotIn("uuid", [$mediaUuid])->where("collection_name", "profile_image")->delete();

        $user->image_id = $media->id;
        $user->save();

        return $this->responseWithData([
            "profile_image_url" => $media->getUrl('small')
        ]);
    }
}
