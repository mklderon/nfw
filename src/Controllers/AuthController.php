<?php

namespace App\Controllers;

use Core\Http\{Request, Response};
use Core\Factories\ServiceFactory;

class AuthController
{
    public function __construct(
        private ServiceFactory $serviceFactory
    ) {}

    public function login(Request $request, Response $response)
    {
        $service = $this->serviceFactory->getService('auth', 'login');
        return $service->login($request, $response);
    }
}