<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaFormRequest;
use Illuminate\Support\Str;


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
            "media_id" => $media->id,
            "media_id_string" => $filename,
            "media_url" => $media->getUrl()
        ]);
    }
}
