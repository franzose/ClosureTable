<?php namespace Franzose\ClosureTable\Generators;


class Model extends Generator {

    /**
     * @param array $names
     * @param string $path
     * @return string
     */
    public function create(array $names, $path)
    {
        $entityClass = $this->classify($names['entity']);
        $closureTableClass = $this->classify($names['closure']);

        $path = $this->getPath($names['closure'], $path);
        $stub = $this->getStub('closuretable', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_class' => $closureTableClass,
            'closure_table' => $this->tableize($names['closure'])
        ]));

        $ctinterface = $names['closure'].'Interface';
        $path = $this->getPath($names['closure'].'Interface', $path);
        $stub = $this->getStub('closuretableinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_class' => $closureTableClass
        ]));

        $path = $this->getPath($names['entity'], $path);
        $stub = $this->getStub('entity', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entityClass,
            'entity_table' => $this->tableize($names['entity']),
            'closure_interface' => $this->classify($ctinterface)
        ]));

        $path = $this->getPath($names['entity'].'Interface', $path);
        $stub = $this->getStub('entityinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entityClass
        ]));

        $path = $this->getPath($names['entity'].'Repository', $path);
        $stub = $this->getStub('entity', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entityClass
        ]));

        return $path;
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