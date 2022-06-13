<?php

namespace App\Rules;

use App\Services\PhoneNumberValidator;
use App\Traits\LocationTrait;
use Illuminate\Contracts\Validation\Rule;

class Phone implements Rule
{
    use LocationTrait;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->validatePhoneNumber($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is invalid.';
    }

    private function validatePhoneNumber($phone): bool
    {
        $phoneNumberValidator = new PhoneNumberValidator();
        $countryCode = $this->getCountryCodeFromLocation();
        $phoneNumberResponse = $phoneNumberValidator->phoneNumberIsValid($phone, $countryCode);
        return $phoneNumberResponse->success ? $phoneNumberResponse->isValid : false;
    }
}
