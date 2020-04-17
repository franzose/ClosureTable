<?php
namespace Franzose\ClosureTable\Generators;

use Franzose\ClosureTable\Extensions\Str as ExtStr;
use Illuminate\Support\Str;

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
        $closureClass = ucfirst($options['closure']);
        $namespaceWithDelimiter = $options['namespace'] . '\\';

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'entity_class' => ucfirst($options['entity']),
            'entity_table' => $options['entity-table'],
            'closure_class' => Str::startsWith($closureClass, $namespaceWithDelimiter)
                ? $closureClass
                : $namespaceWithDelimiter . $closureClass,
        ]));

        // Second, we make closure classes
        $paths[] = $path = $this->getPath($qualifiedClosureName, $options['models-path']);
        $stub = $this->getStub('closuretable', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'closure_class' => $closureClass,
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
        $delimpos = strrpos($name, '\\');
        $filename = $delimpos === false
            ? ExtStr::classify($name)
            : substr(ExtStr::classify($name), $delimpos + 1);

        return $path . '/' . $filename . '.php';
    }
}
