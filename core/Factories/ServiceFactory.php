<?php

namespace Core\Factories;

use Core\System\Container;
use Core\Validation\Validator;
use Core\Contracts\BaseServiceInterface;
use InvalidArgumentException;

class ServiceFactory {
    private $container;
    private $serviceMap;

    public function __construct(Container $container, array $serviceMap) {
        $this->container = $container;
        $this->serviceMap = $serviceMap;
    }

    public function getService(string $entity, string $action = 'create'): BaseServiceInterface {
        $entity = strtolower($entity);
        if (!isset($this->serviceMap[$entity])) {
            throw new InvalidArgumentException("Entidad no soportada: $entity");
        }

        $config = $this->serviceMap[$entity];
        $repository = $this->container->make($config['repository']);
        $validator = new Validator(
            $config['validation'][$action] ?? [],
            []
        );

        // Resolver dependencias adicionales definidas en el serviceMap
        $dependencies = [
            'repository' => $repository,
            'validator' => $validator,
        ];
        if (isset($config['dependencies'])) {
            foreach ($config['dependencies'] as $key => $class) {
                $dependencies[$key] = $this->container->make($class);
            }
        }

        // Usar el contenedor para instanciar el servicio con todas las dependencias
        return $this->container->make($config['service'], $dependencies);
    }
}