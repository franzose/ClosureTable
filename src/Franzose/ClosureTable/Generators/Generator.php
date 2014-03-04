<?php namespace Franzose\ClosureTable\Generators;

use \Illuminate\Filesystem\Filesystem;

/**
 * Class Generator
 * @package Franzose\ClosureTable\Generators
 */
abstract class Generator {

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    abstract public function create(array $options);

    /**
     * @param string $type
     * @return string
     */
    protected function getStubsPath($type)
    {
        return __DIR__.'/stubs/'.$type;
    }

    /**
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getStub($name, $type)
    {
        if (stripos($name, '.php') === false)
        {
            $name = $name.'.php';
        }

        return $this->filesystem->get($this->getStubsPath($type).'/'.$name);
    }

    /**
     * @param string $stub
     * @param array $replacements
     * @return string
     */
    protected function parseStub($stub, array $replacements = [])
    {
        foreach($replacements as $key => $replacement)
        {
            $stub = str_replace('{{'.$key.'}}', $replacement, $stub);
        }

        return $stub;
    }
} 