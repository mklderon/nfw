<?php

namespace App\Controllers;

use Core\Http\{Request, Response};
use Core\Factories\ServiceFactory;

class UsuariosController {
    private $serviceFactory;

    public function __construct(ServiceFactory $serviceFactory) {
        $this->serviceFactory = $serviceFactory;
    }

    public function readAll(Request $request, Response $response) {
        $service = $this->serviceFactory->getService('usuarios', 'readAll');
        return $service->readAll($request, $response);
    }

    public function read(Request $request, Response $response, $parameters) {
        $service = $this->serviceFactory->getService('usuarios', 'read');
        return $service->read($request, $response, $parameters['id']);
    }

    public function create(Request $request, Response $response) {
        $service = $this->serviceFactory->getService('usuarios', 'create');
        return $service->create($request, $response);
    }

    public function update(Request $request, Response $response, $parameters) {
        $service = $this->serviceFactory->getService('usuarios', 'update');
        return $service->update($request, $response, $parameters['id']);
    }

    public function delete(Request $request, Response $response, $parameters) {
        $service = $this->serviceFactory->getService('usuarios', 'delete');
        return $service->delete($request, $response, $parameters['id']);
    }
}