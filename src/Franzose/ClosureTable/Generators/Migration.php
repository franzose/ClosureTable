<?php namespace Franzose\ClosureTable\Generators;


class Migration extends Generator {

    /**
     * @param array $names
     * @param string $path
     * @return array
     */
    public function create(array $names, $path)
    {
        $allPaths = [];
        $migpath = $path;

        $entityClass = $this->getClassName($names['entity']);
        $entityTable = $this->tableize($names['entity']);

        $closureClass = $this->getClassName($names['closure']);
        $closureTable = $this->tableize($names['closure']);

        $allPaths[] = $path = $this->getPath($names['entity'], $migpath);
        $stub = $this->getStub('entity', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_table' => $entityTable,
            'entity_class' => $entityClass
        ]));

        $allPaths[] = $path = $this->getPath($names['closure'], $migpath);
        $stub = $this->getStub('closuretable', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_table' => $closureTable,
            'closure_class' => $closureClass,
            'entity_table'  => $entityTable
        ]));

        return $allPaths;
    }

    /**
     * @param $name
     * @return string
     */
    protected function getName($name)
    {
        return 'create_'.$this->tableize($name).'_table';
    }

    /**
     * @param $name
     * @return string
     */
    protected function getClassName($name)
    {
        return $this->classify($this->getName($name));
    }

    /**
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path.'/'.date('Y_m_d_His').'_'.$this->getName($name).'.php';
    }
} 