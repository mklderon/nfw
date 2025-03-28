<?php

namespace App\Controllers;

use Core\Factories\ServiceFactory;
use Core\Http\{
    Request, Response
};

class ClientesController
{
    private $serviceFactory;

    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    public function readAll(Request $request, Response $response)
    {
        $service = $this->serviceFactory->getService('clientes', 'readAll');
        return $service->readAll($request, $response);
    }

    public function search(Request $request, Response $response, $parameters)
    {
        $service = $this->serviceFactory->getService('clientes', 'search');
        return $service->search($request, $response);
    }

    public function read(Request $request, Response $response, $parameters)
    {
        $service = $this->serviceFactory->getService('clientes', 'read');
        return $service->read($request, $response, $parameters['id']);
    }

    public function create(Request $request, Response $response)
    {
        $service = $this->serviceFactory->getService('clientes', 'create');
        return $service->create($request, $response);
    }

    public function delete(Request $request, Response $response, $parameters)
    {
        $service = $this->serviceFactory->getService('clientes', 'delete');
        return $service->delete($request, $response, $parameters['id']);
    }

    public function update(Request $request, Response $response, $parameters)
    {
        $service = $this->serviceFactory->getService('clientes', 'update');
        return $service->update($request, $response, $parameters['id']);
    }

    // Fatan por realizar
}
