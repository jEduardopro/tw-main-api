<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TweetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->uuid,
            $this->mergeWhen($this->relationLoaded("user"), function () {
                return [
                    "owner" => ProfileResource::make($this->user)
                ];
            }),
            "body" => $this->body,
            $this->mergeWhen($this->relationLoaded("media"), function () {
                return [
                    "images" => MediaResource::collection($this->media)
                ];
            }),
            $this->mergeWhen($this->relationLoaded("mentions"), function () {
                return [
                    "mentions" => ProfileResource::collection($this->mentions)
                ];
            }),
            $this->mergeWhen($this->relationLoaded("reply") && !is_null($this->reply), function () {
                return ['reply_to' => TweetResource::make($this->reply->tweet) ];
            }),
            $this->mergeWhen(!is_null($this->retweets_count), function () {
                return ["retweets_count" => $this->retweets_count];
            }),
            $this->mergeWhen(!is_null($this->replies_count), function () {
                return ["replies_count" => $this->replies_count];
            }),
            $this->mergeWhen(!is_null($this->likes_count), function () {
                return ["likes_count" => $this->likes_count];
            }),
            "creation_date_readable" => $this->getReadableCreationDate(),
            "created_at" => $this->created_at,
        ];
    }
}
