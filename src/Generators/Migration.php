<?php
namespace Franzose\ClosureTable\Generators;

use Carbon\Carbon;
use DateTime;
use Franzose\ClosureTable\Extensions\Str as ExtStr;

/**
 * ClosureTable specific migrations generator class.
 *
 * @package Franzose\ClosureTable\Generators
 */
class Migration extends Generator
{
    /**
     * Creates migration files.
     *
     * @param array $options
     * @return array
     */
    public function create(array $options)
    {
        $paths = [];

        $entityClass = $this->getClassName($options['entity-table']);
        $closureClass = $this->getClassName($options['closure-table']);
        $innoDb = $options['use-innodb'] ? '$table->engine = \'InnoDB\';' : '';

        $dateTime = Carbon::now();

        $paths[] = $path = $this->getPath($dateTime, $options['entity-table'], $options['migrations-path']);
        $stub = $this->getStub('entity', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'entity_table' => $options['entity-table'],
            'entity_class' => $entityClass,
            'innodb' => $innoDb
        ]));

        $dateTime->addSecond();

        $paths[] = $path = $this->getPath($dateTime, $options['closure-table'], $options['migrations-path']);
        $stub = $this->getStub('closuretable', 'migrations');

        $this->filesystem->put($path, $this->parseStub($stub, [
            'closure_table' => $options['closure-table'],
            'closure_class' => $closureClass,
            'entity_table' => $options['entity-table'],
            'innodb' => $innoDb
        ]));

        return $paths;
    }

    /**
     * Constructs migration name in Laravel style.
     *
     * @param $name
     * @return string
     */
    protected function getName($name)
    {
        return 'create_' . ExtStr::tableize($name) . '_table';
    }

    /**
     * Constructs migration class name from the migration name.
     *
     * @param $name
     * @return string
     */
    protected function getClassName($name)
    {
        return ExtStr::classify($this->getName($name));
    }

    /**
     * Constructs path to migration file in Laravel style.
     *
     * @param DateTime $dateTime
     * @param string $name
     * @param string $path
     *
     * @return string
     */
    protected function getPath(DateTime $dateTime, $name, $path)
    {
        return $path . '/' . $dateTime->format('Y_m_d_His') . '_' . $this->getName($name) . '.php';
    }
}
