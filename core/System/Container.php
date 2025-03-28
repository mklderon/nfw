<?php

namespace Core\System;

use Closure;
use Exception;
use ReflectionClass;

class Container {
    private $bindings = [];
    private $instances = [];
    private $tags = [];

    public function bind($abstract, $concrete, $shared = false) {
        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];
    }

    public function singleton($abstract, $concrete) {
        $this->bind($abstract, $concrete, true);
    }

    public function instance($abstract, $instance) {
        $this->instances[$abstract] = $instance;
    }

    public function get($abstract) {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        return $this->make($abstract);
    }

    public function alias($abstract, $alias) {
        $this->bindings[$alias] = $this->bindings[$abstract];
    }

    public function extend($abstract, Closure $closure) {
        $this->bind($abstract, function ($container) use ($closure) {
            return $closure($container->make($abstract), $container);
        });
    }

    public function tag($abstracts, $tag) {
        foreach ((array) $abstracts as $abstract) {
            $this->tags[$tag][] = $abstract;
        }
    }

    public function tagged($tag) {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        $instances = [];
        foreach ($this->tags[$tag] as $abstract) {
            $instances[] = $this->make($abstract);
        }

        return $instances;
    }

    public function factory($abstract, Closure $closure) {
        $this->bind($abstract, $closure);
    }

    public function call($callback, array $parameters = []) {
        return $callback($this, ...$parameters);
    }

    public function when($concrete) {
        return new class($this, $concrete) {
            private $container;
            private $concrete;

            public function __construct($container, $concrete) {
                $this->container = $container;
                $this->concrete = $concrete;
            }

            public function needs($abstract) {
                $this->container->bind($abstract, function ($container) {
                    return $container->make($this->concrete);
                });
            }

            public function give($abstract, $implementation) {
                $this->container->bind($abstract, $implementation);
            }
        };
    }

    public function flush() {
        $this->instances = [];
    }

    public function has($abstract) {
        return isset($this->bindings[$abstract]);
    }

    public function make($abstract) {
        // Si ya existe una instancia compartida, devolverla
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Si hay un binding registrado, usarlo
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract]['concrete'];
            $instance = $concrete instanceof Closure ? $concrete($this) : $this->resolveClass($concrete);

            if ($this->bindings[$abstract]['shared']) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        // Si no hay binding, intentar resolver la clase automáticamente
        if (class_exists($abstract)) {
            return $this->resolveClass($abstract);
        }

        throw new Exception("No se ha registrado el binding para {$abstract} y no puede ser instanciado automáticamente");
    }

    private function resolveClass($class, array $parameters = []) {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new Exception("La clase {$class} no es instanciable");
        }
    
        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            return new $class();
        }
    
        $constructorParameters = $constructor->getParameters();
        $dependencies = [];
        foreach ($constructorParameters as $parameter) {
            $type = $parameter->getType();
    
            // Si el parámetro tiene un valor proporcionado manualmente, usarlo
            if (isset($parameters[$parameter->getPosition()])) {
                $dependencies[] = $parameters[$parameter->getPosition()];
            }
            // Si no tiene tipo y tiene valor por defecto, usar el valor por defecto
            elseif ($type === null && $parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            }
            // Si es un tipo no nativo, resolverlo
            elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            }
            // Si tiene valor por defecto, usarlo
            elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            }
            else {
                throw new Exception("No se puede resolver el parámetro {$parameter->name} de {$class} (tipo no soportado)");
            }
        }
    
        return $reflection->newInstanceArgs($dependencies);
    }
}