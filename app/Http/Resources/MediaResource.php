<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MediaResource extends JsonResource
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
            "url" => $this->getUrl(),
            "conversions" => $this->getConversions(),
            "created_at" => $this->created_at
        ];
    }

    private function getConversions(): array
    {
        $conversions = $this->getGeneratedConversions();

        return $conversions->map(function($conversion, $key) {
            return $this->getUrl($key);
        })->toArray();
    }
}
