<?php namespace Franzose\ClosureTable\Tests\Seeds;

use \Way\Tests\Factory;

class EntitiesSeeder extends \Seeder {

    public function run()
    {
        foreach(range(0, 9) as $idx)
        {
            Factory::create('Franzose\ClosureTable\Entity', [
                'title'     => 'The title',
                'excerpt'   => 'The excerpt',
                'body'      => 'The body',
                'position'  => $idx
            ]);
        }
    }
} 