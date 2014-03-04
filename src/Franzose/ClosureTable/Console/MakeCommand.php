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
        $entity  = $this->option('entity');
        $closure = $this->option('closure') ?: $entity . '_closure';

        $names = [
            'entity' => $entity,
            'closure' => $closure
        ];

        $this->writeMigrations($names);
        $this->writeModels($names);

        $this->call('dump-autoload');
    }

    /**
     * Writes migration files to disk.
     *
     * @param array $names
     * @return void
     */
    protected function writeMigrations(array $names)
    {
        $files = $this->migrator->create($names, $this->getMigrationsPath());

        foreach($files as $file)
        {
            $path = pathinfo($file, PATHINFO_FILENAME);
            $this->line("      <fg=green;options=bold>create</fg=green;options=bold>  $path");
        }
    }

    /**
     * Writes model files to disk.
     *
     * @param array $names
     * @return void
     */
    protected function writeModels(array $names)
    {
        $files = $this->modeler->create($names, $this->getModelsPath());

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
            ['entity', 'e', InputOption::VALUE_REQUIRED, 'Entity table name to use for migrations and models scaffolding.'],
            ['closure', 'c', InputOption::VALUE_OPTIONAL, 'Closure table name to use for migrations and models scaffolding.'],
            ['models-path', 'mdl', InputOption::VALUE_OPTIONAL, 'Models path'],
            ['migrations-path', 'mgr', InputOption::VALUE_OPTIONAL, 'Migrations path'],
        ];
    }
} 