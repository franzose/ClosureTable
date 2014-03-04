<?php namespace Franzose\ClosureTable\Generators;

use Franzose\ClosureTable\Extensions\Str as ExtStr;

/**
 * Class Migration
 * @package Franzose\ClosureTable\Generators
 */
class Migration extends Generator {

    /**
     * @param array $options
     * @return array
     */
    public function create(array $options)
    {
        $paths = [];

        $entityClass = $this->getClassName($options['entity-table']);
        $closureClass = $this->getClassName($options['closure-table']);

        $paths[] = $path = $this->getPath($options['entity-table'], $options['migrations-path']);
        $stub = $this->getStub('entity', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_table' => $options['entity-table'],
            'entity_class' => $entityClass
        ]));

        $paths[] = $path = $this->getPath($options['closure-table'], $options['migrations-path']);
        $stub = $this->getStub('closuretable', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_table' => $options['closure-table'],
            'closure_class' => $closureClass,
            'entity_table'  => $options['entity-table']
        ]));

        return $paths;
    }

    /**
     * @param $name
     * @return string
     */
    protected function getName($name)
    {
        return 'create_' . ExtStr::tableize($name) . '_table';
    }

    /**
     * @param $name
     * @return string
     */
    protected function getClassName($name)
    {
        return ExtStr::classify($this->getName($name));
    }

    /**
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . date('Y_m_d_His') . '_' . $this->getName($name) . '.php';
    }
} 