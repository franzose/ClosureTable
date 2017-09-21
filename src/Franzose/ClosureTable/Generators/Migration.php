<?php
namespace Franzose\ClosureTable\Generators;

use Carbon\Carbon;
use Franzose\ClosureTable\Extensions\Str as ExtStr;

/**
 * ClosureTable specific migrations generator class.
 *
 * @package Franzose\ClosureTable\Generators
 */
class Migration extends Generator
{
    /**
     * @var array
     */
    private $usedTimestamps = [];

    /**
     * Creates migration files.
     *
     * @param array $options
     * @return array
     */
    public function create(array $options)
    {
        $entityClass = $this->getClassName($options['entity-table']);
        $closureClass = $this->getClassName($options['closure-table']);
        $useInnoDB = $options['use-innodb'];
        $stubPrefix = $useInnoDB ? '-innodb' : '';

        $path = $this->getPath($options['entity-table'], $options['migrations-path']);
        $stub = $this->getStub('migration' . $stubPrefix, 'migrations');

        $this->filesystem->put(
            $path,
            $this->parseStub($stub, [
            'entity_table' => $options['entity-table'],
            'entity_class' => $entityClass,
            'closure_table' => $options['closure-table'],
            'closure_class' => $closureClass,
            ])
        );

        return [$path];
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
     * @param $name
     * @param $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        $timestamp = Carbon::now();

        if (in_array($timestamp, $this->usedTimestamps)) {
            $timestamp->addSecond();
        }
        $this->usedTimestamps[] = $timestamp;

        return $path . '/' . $timestamp->format('Y_m_d_His') . '_' . $this->getName($name) . '_migration.php';
    }
}
