<?php
declare(strict_types=1);

namespace App;

Node::createFromArray([
    'id' => 1,
    'children' => [
        [
            'id' => 2,
            'children' => [
                [
                    'id' => 3,
                    'children' => [
                        [
                            'id' => 4,
                            'children' => [
                                [
                                    'id' => 5,
                                    'children' => [
                                        [
                                            'id' => 6,
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
]);

Node::find(4)->deleteSubtree();
Node::find(1)->getDescendants()->pluck('id')->toArray(); // [2, 3, 4]

Node::find(4)->deleteSubtree(true);
Node::find(1)->getDescendants()->pluck('id')->toArray(); // [2, 3]
