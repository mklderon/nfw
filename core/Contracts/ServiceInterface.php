<?php

namespace Core\Contracts;

use Core\Http\{Request, Response};
use Core\Contracts\BaseServiceInterface;

interface ServiceInterface extends BaseServiceInterface {
    public function create(Request $request, Response $response): Response;
    public function read(Request $request, Response $response, $id = null): Response;
    public function update(Request $request, Response $response, $id): Response;
    public function delete(Request $request, Response $response, $id): Response;
}