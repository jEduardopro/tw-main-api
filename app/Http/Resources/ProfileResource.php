<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $authUser = auth('api')->user();
        return [
            "id" => $this->uuid,
            "name" => $this->name,
            "username" => $this->username,
            "email" => $this->email,
            "phone" => $this->phone,
            "country" => $this->country,
            "gender" => $this->gender,
            "description" => $this->description,
            "date_birth" => $this->date_birth,
            $this->mergeWhen($this->relationLoaded('profileImage'), function() {
                return ["image" => MediaResource::make($this->profileImage)];
            }),
            $this->mergeWhen($this->relationLoaded('profileBanner'), function() {
                return ["banner" => MediaResource::make($this->profileBanner)];
            }),
            $this->mergeWhen(!is_null( $this->following_count ), function() {
                return ["following_count" => $this->following_count];
            }),
            $this->mergeWhen(!is_null( $this->followers_count ), function() {
                return ["followers_count" => $this->followers_count];
            }),
            $this->mergeWhen(
                $this->relationLoaded('followers') && $authUser && $authUser->id !== $this->id,
                function() use ($authUser) {
                    return [
                        "following" => $this->followers->contains($authUser->id)
                    ];
            }),
            "readable_joined_date" => $this->getReadableJoinedDate()
        ];
    }
}
