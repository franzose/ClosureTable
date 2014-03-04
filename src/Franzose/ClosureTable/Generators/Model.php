<?php namespace Franzose\ClosureTable\Generators;


class Model extends Generator {

    /**
     * @param array $names
     * @param string $path
     * @return array
     */
    public function create(array $names, $path)
    {
        $allPaths = [];

        // Initial models path
        $modelsPath = $path;

        // Entity class name
        $entityClass = $this->classify($names['entity']);

        // Closure table class name
        $closureTableClass = $this->classify($names['closure']);

        // Closure table interface name
        $closureTableInterface = $closureTableClass.'Interface';

        $closureTableName = $this->tableize($names['closure']);

        // First, we make entity classes
        $allPaths[] = $path = $this->getPath($names['entity'], $modelsPath);
        $stub = $this->getStub('entity', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entityClass,
            'entity_table' => $this->tableize($names['entity']),
            'closure_class' => $closureTableClass,
            'closure_interface' => $this->classify($closureTableInterface)
        ]));

        $allPaths[] = $path = $this->getPath($entityClass.'Interface', $modelsPath);
        $stub = $this->getStub('entityinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entityClass
        ]));

        // Second, we make closure classes
        $allPaths[] = $path = $this->getPath($closureTableClass, $modelsPath);
        $stub = $this->getStub('closuretable', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_class' => $closureTableClass,
            'closure_table' => $closureTableName
        ]));


        $allPaths[] = $path = $this->getPath($closureTableInterface, $modelsPath);
        $stub = $this->getStub('closuretableinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_class' => $closureTableClass
        ]));

        return $allPaths;
    }

    /**
     * @param $name
     * @param $path
     * @return string
     */
    public function getPath($name, $path)
    {
        return $path.'/'.$this->classify($name).'.php';
    }
} 