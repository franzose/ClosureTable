<?php namespace Franzose\ClosureTable\Tests\Seeds;

use \Way\Tests\Factory;

class EntitiesSeeder extends \Seeder {

    public function run()
    {
        $entityClass = 'Franzose\ClosureTable\Entity';
        $ctableClass = 'Franzose\ClosureTable\ClosureTable';

        foreach(range(0, 9) as $idx)
        {
            Factory::create($entityClass, [
                'title'     => 'The title',
                'excerpt'   => 'The excerpt',
                'body'      => 'The body',
                'position'  => $idx
            ]);

            Factory::create($ctableClass, [
                'ancestor' => $idx+1,
                'descendant' => $idx+1,
                'depth' => 0
            ]);
        }
    }
} 