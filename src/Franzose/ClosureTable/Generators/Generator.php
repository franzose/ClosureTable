<?php namespace Franzose\ClosureTable\Generators;

use \Illuminate\Filesystem\Filesystem;

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

    abstract public function create(array $names, $path);

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

    protected function classify($name)
    {
        return studly_case(str_singular($name));
    }

    protected function tableize($name)
    {
        return (ends_with($name, '_closure') ? $name : snake_case(str_plural($name)));
    }
} 