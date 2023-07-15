<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaFormRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    public function store(MediaFormRequest $request)
    {
        $user = $request->user();

        $mediaIdString = Str::random();
        $filename = "{$mediaIdString}.". $request->file('media')->getClientOriginalExtension();

        $media = $user->addMediaFromRequest('media')
            ->usingFileName($filename)
            ->toMediaCollection($request->media_category);

        return $this->responseWithData([
            "media_id" => $media->uuid,
            "media_id_string" => $filename,
            "media_url" => $media->getUrl()
        ]);
    }

    public function destroy($uuid)
    {
        $media = Media::where("uuid", $uuid)->first();

        if (!$media) {
            return $this->responseWithMessage("the resource doesn't exist", 404);
        }

        $media->delete();

        return $this->responseWithMessage("media removed successfully");
    }
}
