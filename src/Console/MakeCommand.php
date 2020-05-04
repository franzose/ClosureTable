<?php

namespace Franzose\ClosureTable\Console;

use Franzose\ClosureTable\Extensions\Str as ExtStr;
use Franzose\ClosureTable\Generators\Migration;
use Franzose\ClosureTable\Generators\Model;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * ClosureTable scaffolding command, created migrations and models.
 *
 * @package Franzose\ClosureTable\Console
 */
class MakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'closuretable:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffolds new migrations and models suitable for ClosureTable.';

    /**
     * Migrations generator instance.
     *
     * @var \Franzose\ClosureTable\Generators\Migration
     */
    private $migrator;

    /**
     * Models generator instance.
     *
     * @var \Franzose\ClosureTable\Generators\Model
     */
    private $modeler;

    /**
     * User input arguments.
     *
     * @var array
     */
    private $options;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Creates a new command instance.
     *
     * @param Migration $migrator
     * @param Model $modeler
     * @param Composer $composer
     */
    public function __construct(Migration $migrator, Model $modeler, Composer $composer)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->modeler = $modeler;
        $this->composer = $composer;
    }

    /**
     * Executes console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->prepareOptions();
        $this->writeMigrations();
        $this->writeModels();
    }

    /**
     * Writes migration files to disk.
     *
     * @return void
     */
    protected function writeMigrations()
    {
        $files = $this->migrator->create($this->options);

        foreach ($files as $file) {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $this->line("      <fg=green;options=bold>create</fg=green;options=bold>  $path");
        }

        $this->composer->dumpAutoloads();
    }

    /**
     * Writes model files to disk.
     *
     * @return void
     */
    protected function writeModels()
    {
        $files = $this->modeler->create($this->options);

        foreach ($files as $file) {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $this->line("      <fg=green;options=bold>create</fg=green;options=bold>  $path");
        }
    }

    protected function getArguments()
    {
        return [
            ['entity', InputArgument::REQUIRED, 'Class name of the entity model']
        ];
    }

    /**
     * Gets the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Namespace for entity and closure classes. Once given, it will override namespaces of the models of entity and closure'],
            ['entity-table', 'et', InputOption::VALUE_OPTIONAL, 'Database table name for entity'],
            ['closure', 'c', InputOption::VALUE_OPTIONAL, 'Class name of the closure (relationships) model'],
            ['closure-table', 'ct', InputOption::VALUE_OPTIONAL, 'Database table name for closure (relationships)'],
            ['models-path', 'mdl', InputOption::VALUE_OPTIONAL, 'Directory in which to put generated models'],
            ['migrations-path', 'mgr', InputOption::VALUE_OPTIONAL, 'Directory in which to put generated migrations'],
            ['use-innodb', 'i', InputOption::VALUE_OPTIONAL, 'Use InnoDB engine (MySQL only)'],
        ];
    }

    /**
     * Prepares user input options to be passed to migrator and modeler instances.
     *
     * @return void
     */
    protected function prepareOptions()
    {
        $entity = $this->argument('entity');
        $options = $this->getOptions();
        $input = array_map(function (array $option) {
            return $this->option($option[0]);
        }, $this->getOptions());

        $this->options[$options[0][0]] = $this->getNamespace($entity, $input[0]);
        $this->options['entity'] = $this->getEntityModelName($entity);
        $this->options[$options[1][0]] = $input[1] ?: ExtStr::tableize($this->options['entity']);
        $this->options[$options[2][0]] = $input[2]
            ? $this->getEntityModelName($input[2])
            : $this->options['entity'] . 'Closure';

        $this->options[$options[3][0]] = $input[3] ?: ExtStr::snake($this->options[$options[2][0]]);
        $this->options[$options[4][0]] = $input[4] ?: app_path();
        $this->options[$options[5][0]] = $input[5] ?: app()->databasePath('migrations');
        $this->options[$options[6][0]] = $input[6] ?: false;
    }

    private function getNamespace($entity, $original)
    {
        if (!empty($original)) {
            return $original;
        }

        $namespace = substr($entity, 0, strrpos($entity, '\\'));

        if (!empty($namespace)) {
            return $namespace;
        }

        return rtrim(app()->getNamespace(), '\\');
    }

    private function getEntityModelName($original)
    {
        $delimpos = strrpos($original, '\\');

        if ($delimpos === false) {
            return $original;
        }

        return substr($original, $delimpos + 1);
    }
}
