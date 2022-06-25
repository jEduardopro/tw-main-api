<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private function response(array $data, $statusCode = 200)
    {
        return response()->json($data, $statusCode);
    }

    protected function responseWithMessage(string $message, $statusCode = 200)
    {
        return $this->response(["message" => $message], $statusCode);
    }

    protected function responseWithErrors(array $errors, $statusCode = 422)
    {
        return $this->response(["errors" => $errors], $statusCode);
    }

    protected function responseWithData(array $data, $statusCode = 200)
    {
        return $this->response($data, $statusCode);
    }

    protected function responseWithResource($resource)
    {
        return $resource;
    }
}
