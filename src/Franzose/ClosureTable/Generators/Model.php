<?php
namespace Franzose\ClosureTable\Generators;

use Franzose\ClosureTable\Extensions\Str as ExtStr;

/**
 * ClosureTable specific models generator class.
 *
 * @package Franzose\ClosureTable\Generators
 */
class Model extends Generator
{
    /**
     * Creates models and interfaces files.
     *
     * @param array $options
     * @return array
     */
    public function create(array $options)
    {
        $paths = [];

        $nsplaceholder = !empty($options['namespace'])
            ? sprintf('namespace %s;', $options['namespace'])
            : '';

        $qualifiedEntityName = $options['entity'];
        $qualifiedClosureName = $options['closure'];

        // First, we make entity classes
        $paths[] = $path = $this->getPath($qualifiedEntityName, $options['models-path']);
        $stub = $this->getStub('entity', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'entity_class' => $options['entity'],
            'entity_table' => $options['entity-table'],
            'closure_class' => $options['namespace'] . '\\' . $options['closure'],
        ]));

        // Second, we make closure classes
        $paths[] = $path = $this->getPath($qualifiedClosureName, $options['models-path']);
        $stub = $this->getStub('closuretable', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'closure_class' => $options['closure'],
            'closure_table' => $options['closure-table']
        ]));

        return $paths;
    }

    /**
     * Constructs path to a model.
     *
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . ExtStr::classify($name) . '.php';
    }
}
