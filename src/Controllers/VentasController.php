<?php

namespace App\Controllers;

use Core\Http\{Request, Response};
use Core\Factories\ServiceFactory;

class VentasController {
    private $serviceFactory;

    public function __construct(ServiceFactory $serviceFactory) {
        $this->serviceFactory = $serviceFactory;
    }

    public function readAll(Request $request, Response $response) {
        $service = $this->serviceFactory->getService('ventas','readAll');
        return $service->readAll($request, $response);
    }

    public function read(Request $request, Response $response, $parameters) {
        $service = $this->serviceFactory->getService('ventas','read');
        return $service->read($request, $response, $parameters['id']);
    }

    public function update(Request $request, Response $response, $parameters) {
        $service = $this->serviceFactory->getService('ventas','update');
        return $service->update($request, $response, $parameters['id']);
    }
}