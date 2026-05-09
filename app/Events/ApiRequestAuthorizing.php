<?php

namespace App\Events;

use App\Models\ApiToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestAuthorizing
{
    use Dispatchable;

    public ?Response $denyResponse = null;

    public function __construct(
        public readonly ApiToken $token,
        public readonly Request $request,
    ) {
    }

    public function deny(Response $response): void
    {
        $this->denyResponse = $response;
    }
}
