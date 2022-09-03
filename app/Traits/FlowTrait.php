<?php

namespace App\Traits;

use App\Models\Flow;
use Illuminate\Support\Str;


trait FlowTrait
{
    public function createNewFlow(string $taskName, array $payload): Flow
    {
        return Flow::create([
            "name" => $taskName,
            "token" => $this->generateFlowToken(),
            "payload" => $payload
        ]);
    }

    public function findFlowByToken(string $token): Flow | bool
    {
        $flow = Flow::where('token', $token)->first();
        if (!$flow) {
            return false;
        }

        return $flow;
    }

    public function flowIsExpired(Flow $flow): bool
    {
        $currentDate = now()->subMinutes(10);
        if ($currentDate->greaterThan($flow->created_at)) {
            return true;
        }
        return false;
    }

    public function generateFlowToken(): string
    {
        $now = now()->timestamp;
        $time = now()->addMinutes(10)->timestamp;
        $token = Str::random(16);
        return "g;$now:-$time:$token";
    }

}
