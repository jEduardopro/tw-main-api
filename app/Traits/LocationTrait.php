<?php

namespace App\Traits;

use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

trait LocationTrait
{
    public function getCountryCodeFromLocation(): string
    {
        $locationData = $this->getLocationData();
        if ($locationData) {
            return $locationData->countryCode;
        }
        return env("APP_DEFAULT_ISO_CODE");
    }

    public function getLocationData(): Position|bool
    {
        $ip = env('APP_ENV') != "production" ? env('IP_TEST_LOCATION') : request()->ip();
        $data = Location::get($ip);
        return $data ?? false;
    }
}
