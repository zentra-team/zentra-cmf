<?php

namespace App\Events;

use App\Models\ApiToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestServed
{
    use Dispatchable;

    public function __construct(
        public readonly ApiToken $token,
        public readonly Request $request,
        public readonly Response $response,
    ) {
    }
}
