<?php
namespace Franzose\ClosureTable\Tests;

use \DB;
use Illuminate\Database\Seeder;

class EntitiesSeeder extends Seeder
{
    public function run()
    {
        $entitiesSql = 'insert into entities (parent_id, title, excerpt, body, position) values(?, ?, ?, ?, ?)';
        $closuresSql = 'insert into entities_closure (ancestor, descendant, depth) values(?, ?, ?)';

        // 1
        // 2
        // 3
        // 4
        // 5
        // 6
        // 7
        // 8
        // 9 > 10 > 11 > 12
        // 9 > 13
        // 9 > 14
        // 9 > 15

        foreach (range(0, 8) as $idx) {
            DB::insert($entitiesSql, [null, 'The title', 'The excerpt', 'The body', $idx]);
            DB::insert($closuresSql, [$idx + 1, $idx + 1, 0]);
        }

        DB::insert($entitiesSql, [9, 'The title', 'The excerpt', 'The body', 0]);
        DB::insert($entitiesSql, [10, 'The title', 'The excerpt', 'The body', 0]);
        DB::insert($entitiesSql, [11, 'The title', 'The excerpt', 'The body', 0]);
        DB::insert($closuresSql, [10, 10, 0]);
        DB::insert($closuresSql, [11, 11, 0]);
        DB::insert($closuresSql, [12, 12, 0]);

        DB::insert($closuresSql, [9, 10, 1]);
        DB::insert($closuresSql, [10, 11, 1]);
        DB::insert($closuresSql, [11, 12, 1]);

        DB::insert($closuresSql, [9, 11, 2]);
        DB::insert($closuresSql, [10, 12, 2]);
        DB::insert($closuresSql, [9, 12, 3]);

        DB::insert($entitiesSql, [9, 'The title', 'The excerpt', 'The body', 1]);
        DB::insert($closuresSql, [13, 13, 0]);
        DB::insert($closuresSql, [9, 13, 1]);

        DB::insert($entitiesSql, [9, 'The title', 'The excerpt', 'The body', 2]);
        DB::insert($closuresSql, [14, 14, 0]);
        DB::insert($closuresSql, [9, 14, 1]);

        DB::insert($entitiesSql, [9, 'The title', 'The excerpt', 'The body', 3]);
        DB::insert($closuresSql, [15, 15, 0]);
        DB::insert($closuresSql, [9, 15, 1]);
    }
}
