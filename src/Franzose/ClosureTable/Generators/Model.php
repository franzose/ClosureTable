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
        $entity = $names['entity'];

        // Namespace name
        $ns = $names['namespace'] ?: substr($entity, 0, strrpos($entity, '\\'));
        $ns = str_replace('\\', '/', $ns);
        $dirs = explode('/', $ns);
        $dirsnum = count($dirs);

        // Entity path
        $epath = (strrpos($entity, '\\') !== false ? str_replace('\\', '/', $entity) : $ns . '/' . $entity);

        // Entity table name
        $etable = $names['entity-table'] ?: $this->tableize($entity);

        // Closure table class name
        $closure = $names['closure'] ?: $entity . 'Closure';
        $cpath = (strrpos($closure, '\\') !== false ? str_replace('\\', '/', $closure) : $ns . '/' . $closure);

        // Closure table name
        $ctable = $names['closure-table'] ?: $this->tableize($closure);

        // Closure table interface name
        $closureInterface = $closure.'Interface';
        $cipath = $cpath.'Interface';

        if ($dirsnum > 1)
        {
            $mkpath = $modelsPath . '/';

            for($i=0; $i<$dirsnum; $i++)
            {
                $mkpath .= $dirs[$i] . '/';

                $this->filesystem->makeDirectory($mkpath);
            }
        }

        // First, we make entity classes
        $allPaths[] = $path = $this->getPath($epath, $modelsPath);
        $stub = $this->getStub('entity', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entity,
            'entity_table' => $etable,
            'closure_class' => $closure,
            'closure_interface' => $closureInterface
        ]));

        $allPaths[] = $path = $this->getPath($epath.'Interface', $modelsPath);
        $stub = $this->getStub('entityinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_class' => $entity
        ]));

        // Second, we make closure classes
        $allPaths[] = $path = $this->getPath($cpath, $modelsPath);
        $stub = $this->getStub('closuretable', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_class' => $closure,
            'closure_table' => $ctable
        ]));


        $allPaths[] = $path = $this->getPath($cipath, $modelsPath);
        $stub = $this->getStub('closuretableinterface', 'models');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_class' => $closure
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
        return $path . '/' . $this->classify($name) . '.php';
    }
} 