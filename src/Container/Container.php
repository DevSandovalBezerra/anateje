<?php

declare(strict_types=1);

namespace Anateje\Container;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    private array $factories = [];
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        unset($this->instances[$id]);
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $this->instances[$id] = ($this->factories[$id])($this);
            return $this->instances[$id];
        }

        if (!class_exists($id)) {
            throw new RuntimeException("Serviço não encontrado: {$id}");
        }

        $this->instances[$id] = $this->autowire($id);
        return $this->instances[$id];
    }

    private function autowire(string $className): object
    {
        $ref = new ReflectionClass($className);
        $ctor = $ref->getConstructor();
        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return new $className();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new RuntimeException("Não foi possível autowire {$className}: parâmetro {$param->getName()} sem type-hint de classe/interface.");
            }
            $args[] = $this->get($type->getName());
        }

        return $ref->newInstanceArgs($args);
    }
}

