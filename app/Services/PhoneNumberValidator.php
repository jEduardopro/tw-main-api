<?php

namespace App\Services;

use Exception;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberValidator
{
    private $pnu;
    private $response;

    public function __construct()
    {
        $this->pnu = PhoneNumberUtil::getInstance();
        $this->response = $this->getResponseObject();
    }

    public function phoneNumberIsValid(string $phoneNumber, string $countryCode)
    {
        try {
            $phoneNumberObject = $this->pnu->parse($phoneNumber, $countryCode);

            $this->response->originalPhoneNumber = $phoneNumber;

            $this->response->isValid = $this->pnu->isValidNumber($phoneNumberObject);
            $this->response->success = true;


        } catch (Exception $e) {
            $this->response->message = "Error trying validate phone number: " . $e->getMessage();
            $this->response->success = false;
        } finally {
            return $this->response;
        }
    }

    public function getPhoneNumberValidated(string $phoneNumber, string $countryCode)
    {
        try {
            $phoneNumberObject = $this->pnu->parse($phoneNumber, $countryCode);

            $this->response->originalPhoneNumber = $phoneNumber;

            if ($this->pnu->isValidNumber($phoneNumberObject)) {
                $this->response->isValid = true;
                $this->response->success = true;
                $this->response->phoneNumberValidated = $this->getPhoneNumberData($phoneNumberObject);
                $this->response->message = "validation success";
            }

        } catch (Exception $e) {
            $this->response->message = "Error trying validate phone number: ". $e->getMessage();
            $this->response->success = false;
        } finally {
            return $this->response;
        }
    }

    private function getPhoneNumberData(PhoneNumber $phoneNumberObject): array
    {
        return [
            'countryCode' => $phoneNumberObject->getCountryCode(),
            'nationalNumber' => $phoneNumberObject->getNationalNumber(),
            'E164' => $this->pnu->format($phoneNumberObject, PhoneNumberFormat::E164)
        ];
    }

    private function getResponseObject()
    {
        $response = new \stdClass();
        $response->isValid = false;
        $response->originalPhoneNumber = null;
        $response->phoneNumberValidated = null;
        $response->success = false;
        $response->message = "";

        return $response;
    }
}
