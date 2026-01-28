<?php
declare(strict_types=1);

namespace App;

use Franzose\ClosureTable\EntityCollection;

Node::createFromArray([
    'id' => 1,
    'children' => [
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
        ['id' => 5],
        [
            'id' => 6,
            'children' => [
                ['id' => 7],
                ['id' => 8],
            ]
        ],
    ]
]);

/** @var EntityCollection $children */
$children = Node::find(1)->getChildren();
$children->getChildAt(1)->id; // 3
$children->getFirstChild()->id; // 2
$children->getLastChild()->id; // 6
$children->getRange(1)->pluck('id')->toArray(); // [3, 4, 5, 6]
$children->getRange(1, 3)->pluck('id')->toArray(); // [3, 4, 5]
$children->getNeighbors(2)->pluck('id')->toArray(); // [3, 5]
$children->getPrevSiblings(2)->pluck('id')->toArray(); // [2, 3]
$children->getNextSiblings(2)->pluck('id')->toArray(); // [5, 6]
$children->getChildrenOf(4)->pluck('id')->toArray(); // [7, 8]
$children->hasChildren(4); // true
$tree = $children->toTree();
