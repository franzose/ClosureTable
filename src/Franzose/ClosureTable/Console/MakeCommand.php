<?php namespace Franzose\ClosureTable\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Franzose\ClosureTable\Generators\Migration;
use Franzose\ClosureTable\Generators\Model;

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
     * Input arguments
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
     */
    public function fire()
    {
        foreach($this->getOptions() as $option)
        {
            $this->options[$option[0]] = $this->option($option[0]);
        }

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
        $files = $this->migrator->create($this->options, $this->getMigrationsPath());

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
        $files = $this->modeler->create($this->options, $this->getModelsPath());

        foreach($files as $file)
        {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $this->line("      <fg=green;options=bold>create</fg=green;options=bold>  $path");
        }
    }

    /**
     * Get the path to the migrations directory.
     *
     * @return string
     */
    protected function getMigrationsPath()
    {
        $custom = $this->option('migrations-path');

        if ($custom)
        {
            $path = $this->laravel['path'] . '/' . $custom;
        }
        else
        {
            $path = $this->laravel['path'] . '/database/migrations';
        }

        return $path;
    }

    /**
     * Get the path to the models directory.
     *
     * @return string
     */
    protected function getModelsPath()
    {
        $custom = $this->option('models-path');

        if ($custom)
        {
            $path = $this->laravel['path'] . '/' . $custom;
        }
        else
        {
            $path = $this->laravel['path'] . '/models';
        }

        return $path;
    }

    /**
     * Get the console command options.
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
} 