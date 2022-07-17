<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class NotificationResource extends JsonResource
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
            "id" => $this->id,
            "type" => $this->getReadableNotificationType(),
            $this->mergeWhen($this->relationLoaded("senderable"), function () {
                return ["sender" => ProfileResource::make( $this->senderable )];
            }),
            "data" => $this->data,
            "read_at" => $this->read_at,
            "created_at" => $this->created_at,
        ];
    }

    private function getReadableNotificationType()
    {
        return Str::of( $this->type )->explode("\\")->last();
    }
}
