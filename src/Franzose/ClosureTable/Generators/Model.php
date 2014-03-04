<?php namespace Franzose\ClosureTable\Generators;

use Franzose\ClosureTable\Extensions\Str as ExtStr;

/**
 * Class Model
 * @package Franzose\ClosureTable\Generators
 */
class Model extends Generator {

    /**
     * @param array $options
     * @return array
     */
    public function create(array $options)
    {
        $paths = [];

        $nspath = str_replace('\\', '/', $options['namespace']);
        $nsplaceholder = (!empty($options['namespace']) ? "namespace " . $options['namespace'] . ";" : '');

        $closureInterface = $options['closure'].'Interface';
        $qualifiedEntityName = $nspath . '/' . $options['entity'];
        $qualifiedEntityInterfaceName = $qualifiedEntityName . 'Interface';
        $qualifiedClosureName = $nspath . '/' . $options['closure'];
        $qualifiedClosureInterfaceName = $qualifiedClosureName . 'Interface';

        $this->makeDirectories(explode('/', $nspath), $options['models-path']);

        // First, we make entity classes
        $paths[] = $path = $this->getPath($qualifiedEntityName, $options['models-path']);
        $stub = $this->getStub('entity', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'entity_class' => $options['entity'],
            'entity_table' => $options['entity-table'],
            'closure_class' => $options['namespace'] . '\\' . $options['closure'],
            'closure_class_short' => $options['closure'],
            'closure_interface' => $closureInterface
        ]));

        $paths[] = $path = $this->getPath($qualifiedEntityInterfaceName, $options['models-path']);
        $stub = $this->getStub('entityinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'entity_class' => $options['entity']
        ]));

        // Second, we make closure classes
        $paths[] = $path = $this->getPath($qualifiedClosureName, $options['models-path']);
        $stub = $this->getStub('closuretable', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'closure_class' => $options['closure'],
            'closure_table' => $options['closure-table']
        ]));


        $paths[] = $path = $this->getPath($qualifiedClosureInterfaceName, $options['models-path']);
        $stub = $this->getStub('closuretableinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'namespace' => $nsplaceholder,
            'closure_class' => $options['closure']
        ]));

        return $paths;
    }

    /**
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . ExtStr::classify($name) . '.php';
    }

    /**
     * @param array $directories
     * @param string $modelsPath
     */
    protected function makeDirectories(array $directories, $modelsPath)
    {
        $dirsnum = count($directories);

        if ($dirsnum > 1)
        {
            $mkpath = $modelsPath . '/';

            for($i=0; $i<$dirsnum; $i++)
            {
                $mkpath .= $directories[$i] . '/';

                $this->filesystem->makeDirectory($mkpath);
            }
        }
    }
} 