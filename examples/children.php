<?php
declare(strict_types=1);

namespace App;

$nodes = [
    new Node(['id' => 1]),
    new Node(['id' => 2, 'parent_id' => 1]),
    new Node(['id' => 3, 'parent_id' => 1]),
    new Node(['id' => 4, 'parent_id' => 1]),
    new Node(['id' => 5, 'parent_id' => 1]),
    new Node(['id' => 6, 'parent_id' => 2]),
    new Node(['id' => 7, 'parent_id' => 3])
];

foreach ($nodes as $node) {
    $node->save();
}

Node::find(1)->getChildren()->pluck('id')->toArray(); // [2, 3, 4, 5]
Node::find(1)->countChildren(); // 3
Node::find(1)->hasChildren(); // true

// get child at the second position (positions start from zero)
Node::find(1)->getChildAt(1)->id; // 3

Node::find(1)->getChildrenRange(1)->pluck('id')->toArray(); // [3, 4, 5]
Node::find(1)->getChildrenRange(0, 2)->pluck('id')->toArray(); // [2, 3, 4]

Node::find(1)->getFirstChild()->id; // 2
Node::find(1)->getLastChild()->id; // 5

Node::find(6)->countChildren(); // 0
Node::find(6)->hasChildren(); // false

Node::find(6)->addChild(new Node(['id' => 7]));

Node::find(1)->addChildren([new Node(['id' => 8]), new Node(['id' => 9])], 2);
Node::find(1)->getChildren()->pluck('position', 'id')->toArray(); // [2 => 0, 3 => 1, 8 => 2, 9 => 3, 4 => 4, 5 => 5]

// remove child by its position
Node::find(1)->removeChild(2);
Node::find(1)->getChildren()->pluck('position', 'id')->toArray(); // [2 => 0, 3 => 1, 9 => 2, 4 => 3, 5 => 4]

Node::find(1)->removeChildren(2, 4);
Node::find(1)->getChildren()->pluck('position', 'id')->toArray(); // [2 => 0, 3 => 1]
