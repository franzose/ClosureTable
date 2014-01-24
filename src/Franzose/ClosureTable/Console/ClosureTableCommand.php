<?php namespace Franzose\ClosureTable\Console;

use Franzose\ClosureTable\ClosureTableServiceProvider as CT;
use Illuminate\Console\Command;

class ClosureTableCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'closuretable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get ClosureTable version notice.';

    /**
     * Executes console command.
     */
    public function fire()
    {
        $this->info('ClosureTable v'.CT::VERSION);
        $this->line('A Closure Table pattern implementation for Laravel framework.');
        $this->comment('Copyright (c) 2013-2014 Jan Iwanow');
    }
} 