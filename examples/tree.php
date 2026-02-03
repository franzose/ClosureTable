<?php
declare(strict_types=1);

namespace App;

// Explicit positions are preserved on initial insert, even if input order is different.
Node::createFromArray([
    [
        'id' => 1,
        'children' => [
            ['id' => 2, 'position' => 2],
            ['id' => 3, 'position' => 0],
            ['id' => 4, 'position' => 1],
            [
                'id' => 5,
                'children' => [
                    ['id' => 6],
                ],
            ],
        ],
    ],
]);

Node::find(4)->deleteSubtree();
Node::find(1)->getDescendants()->pluck('id')->toArray(); // [2, 3, 4]

Node::find(4)->deleteSubtree(true);
Node::find(1)->getDescendants()->pluck('id')->toArray(); // [2, 3]
