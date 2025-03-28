<?php

namespace App\Contracts;

use Core\Http\{Request, Response};
use Core\Contracts\BaseServiceInterface;

interface AuthServiceInterface extends BaseServiceInterface
{
    public function login(Request $request, Response $response);
    public function refreshToken(Request $request, Response $response);
}