<?php namespace Franzose\ClosureTable\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
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
        $entity  = $this->argument('entity');
        $closure = $this->argument('closure');

        $this->info($entity);
        $this->info($closure);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['entity', InputArgument::REQUIRED, 'Entity table name to use for migrations and models scaffolding.'],
            ['closure', InputArgument::OPTIONAL, 'Closure table name to use for migrations and models scaffolding.']
        ];
    }
} 