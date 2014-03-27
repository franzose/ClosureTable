<?php namespace Franzose\ClosureTable\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Franzose\ClosureTable\Generators\Migration;
use Franzose\ClosureTable\Generators\Model;
use Franzose\ClosureTable\Extensions\Str as ExtStr;

/**
 * ClosureTable scaffolding command, created migrations and models.
 *
 * @package Franzose\ClosureTable\Console
 */
class MakeCommand extends Command {

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
    protected $migrator;

    /**
     * Models generator instance.
     *
     * @var \Franzose\ClosureTable\Generators\Model
     */
    protected $modeler;

    /**
     * User input arguments.
     *
     * @var array
     */
    protected $options;

    /**
     * Creates a new command instance.
     *
     * @param Migration $migrator
     * @param Model $modeler
     */
    public function __construct(Migration $migrator, Model $modeler)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->modeler = $modeler;
    }

    /**
     * Executes console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->prepareOptions();
        $this->writeMigrations();
        $this->writeModels();

        $this->call('dump-autoload');
    }

    /**
     * Writes migration files to disk.
     *
     * @return void
     */
    protected function writeMigrations()
    {
        $files = $this->migrator->create($this->options);

        foreach($files as $file)
        {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $this->line("      <fg=green;options=bold>create</fg=green;options=bold>  $path");
        }
    }

    /**
     * Writes model files to disk.
     *
     * @return void
     */
    protected function writeModels()
    {
        $files = $this->modeler->create($this->options);

        foreach($files as $file)
        {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $this->line("      <fg=green;options=bold>create</fg=green;options=bold>  $path");
        }
    }

    /**
     * Gets the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Namespace for entity and its closure.'],
            ['entity', 'e', InputOption::VALUE_REQUIRED, 'Entity class name.'],
            ['entity-table', 'et', InputOption::VALUE_OPTIONAL, 'Entity table name.'],
            ['closure', 'c', InputOption::VALUE_OPTIONAL, 'Closure class name'],
            ['closure-table', 'ct', InputOption::VALUE_OPTIONAL, 'Closure table name.'],
            ['models-path', 'mdl', InputOption::VALUE_OPTIONAL, 'Models path.'],
            ['migrations-path', 'mgr', InputOption::VALUE_OPTIONAL, 'Migrations path.'],
        ];
    }

    /**
     * Prepares user input options to be passed to migrator and modeler instances.
     *
     * @return void
     */
    protected function prepareOptions()
    {
        $options = $this->getOptions();
        $input = [];

        foreach($options as $option)
        {
            $input[] = $this->option($option[0]);
        }

        $larapath = $this->laravel['path'];
        $lastnsdelim = strrpos($input[1], '\\');

        $this->options[$options[0][0]] = $input[0] ?: substr($input[1], 0, $lastnsdelim);
        $this->options[$options[1][0]] = substr($input[1], $lastnsdelim);
        $this->options[$options[2][0]] = $input[2] ?: ExtStr::tableize($input[1]);
        $this->options[$options[3][0]] = $input[3] ?: $this->options[$options[1][0]].'Closure';
        $this->options[$options[4][0]] = $input[4] ?: ExtStr::tableize($input[1].'Closure');
        $this->options[$options[5][0]] = $input[5] ? $larapath . '/' . $input[5] : $larapath . '/models';
        $this->options[$options[6][0]] = $input[6] ? $larapath . '/' . $input[6] : $larapath . '/database/migrations';
    }
} 