<?php
declare(strict_types=1);

namespace App;

$nodes = [
    new Node(['id' => 1]),
    new Node(['id' => 2]),
    new Node(['id' => 3]),
    new Node(['id' => 4, 'parent_id' => 1])
];

foreach ($nodes as $node) {
    $node->save();
}

Node::getRoots()->pluck('id')->toArray(); // [1, 2, 3]
Node::find(1)->isRoot(); // true
Node::find(1)->isParent(); // true
Node::find(4)->isRoot(); // false
Node::find(4)->isParent(); // false

// make node 4 a root at the fourth position (1 => 0, 2 => 1, 3 => 2, 4 => 3)
$node = Node::find(4)->makeRoot(3);
$node->isRoot(); // true
$node->position; // 3

Node::find(4)->moveTo(0, Node::find(2)); // same as Node::find(4)->moveTo(0, 2);
Node::find(2)->getChildren()->pluck('id')->toArray(); // [4]

// move a node within the same parent to reorder siblings
Node::find(15)->moveTo(1, 9);
