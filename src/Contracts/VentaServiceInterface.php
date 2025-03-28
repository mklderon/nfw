<?php

namespace App\Contracts;

use Core\Http\{Request, Response};
use Core\Contracts\BaseServiceInterface;

interface VentaServiceInterface extends BaseServiceInterface
{
    public function readAll(Request $request, Response $response);
}