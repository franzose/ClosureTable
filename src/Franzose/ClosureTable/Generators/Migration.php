<?php namespace Franzose\ClosureTable\Generators;


class Migration extends Generator {

    /**
     * @param array $names
     * @param string $path
     * @return string
     */
    public function create(array $names, $path)
    {
        $path = $this->getPath($names['closure'], $path);
        $stub = $this->getStub('closuretable', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_table' => $this->tableize($names['closure']),
            'closure_class' => $this->classify($names['closure']),
            'entity_table'  => $this->tableize($names['entity'])
        ]));

        $path = $this->getPath($names['entity'], $path);
        $stub = $this->getStub('entity', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_table' => $this->tableize($names['entity']),
            'entity_class' => $this->classify($names['entity'])
        ]));

        return $path;
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