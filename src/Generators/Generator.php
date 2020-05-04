<?php
namespace Franzose\ClosureTable\Generators;

use Illuminate\Filesystem\Filesystem;

/**
 * Basic generator class.
 *
 * @package Franzose\ClosureTable\Generators
 */
abstract class Generator
{
    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Constructs the generator.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Generates files from stubs.
     *
     * @param array $options
     * @return mixed
     */
    abstract public function create(array $options);

    /**
     * Gets stub files absolute path.
     *
     * @param string $type
     * @return string
     */
    protected function getStubsPath($type)
    {
        return __DIR__ . '/stubs/' . $type;
    }

    /**
     * Get a stub file by name.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getStub($name, $type)
    {
        if (stripos($name, '.php') === false) {
            $name = $name . '.php';
        }

        return $this->filesystem->get($this->getStubsPath($type) . '/' . $name);
    }

    /**
     * Parses a stub file replacing tags with provided values.
     *
     * @param string $stub
     * @param array $replacements
     * @return string
     */
    protected function parseStub($stub, array $replacements = [])
    {
        foreach ($replacements as $key => $replacement) {
            $stub = str_replace('{{' . $key . '}}', $replacement, $stub);
        }

        return $stub;
    }
}
