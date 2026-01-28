<?php
declare(strict_types=1);

namespace App;

$nodes = [
    new Node(['id' => 1]),
    new Node(['id' => 2, 'parent_id' => 1]),
    new Node(['id' => 3, 'parent_id' => 1]),
    new Node(['id' => 4, 'parent_id' => 1]),
    new Node(['id' => 5, 'parent_id' => 1]),
    new Node(['id' => 6, 'parent_id' => 1]),
    new Node(['id' => 7, 'parent_id' => 1])
];

foreach ($nodes as $node) {
    $node->save();
}

Node::find(7)->getFirstSibling()->id; // 2
Node::find(7)->getSiblingAt(0); // 2
Node::find(2)->getLastSibling(); // 7
Node::find(7)->getPrevSibling()->id; // 6
Node::find(7)->getPrevSiblings()->pluck('id')->toArray(); // [2, 3, 4, 5, 6]
Node::find(7)->countPrevSiblings(); // 5
Node::find(7)->hasPrevSiblings(); // true

Node::find(2)->getNextSibling()->id; // 3
Node::find(2)->getNextSiblings()->pluck('id')->toArray(); // [3, 4, 5, 6, 7]
Node::find(2)->countNextSiblings(); // 5
Node::find(2)->hasNextSiblings(); // true

Node::find(3)->getSiblings()->pluck('id')->toArray(); // [2, 4, 5, 6, 7]
Node::find(3)->getNeighbors()->pluck('id')->toArray(); // [2, 4]
Node::find(3)->countSiblings(); // 5
Node::find(3)->hasSiblings(); // true

Node::find(2)->getSiblingsRange(2)->pluck('id')->toArray(); // [4, 5, 6, 7]
Node::find(2)->getSiblingsRange(2, 4)->pluck('id')->toArray(); // [4, 5, 6]

Node::find(4)->addSibling(new Node(['id' => 8]));
Node::find(4)->getNextSiblings()->pluck('id')->toArray(); // [5, 6, 7, 8]

Node::find(4)->addSibling(new Node(['id' => 9]), 1);
Node::find(1)->getChildren()->pluck('position', 'id')->toArray();
// [2 => 0, 9 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5, 7 => 6, 8 => 7]

Node::find(8)->addSiblings([new Node(['id' => 10]), new Node(['id' => 11])]);
Node::find(1)->getChildren()->pluck('position', 'id')->toArray();
// [2 => 0, 9 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5, 7 => 6, 8 => 7, 10 => 8, 11 => 9]

Node::find(2)->addSiblings([new Node(['id' => 12]), new Node(['id' => 13])], 3);
Node::find(1)->getChildren()->pluck('position', 'id')->toArray();
// [2 => 0, 9 => 1, 3 => 2, 12 => 3, 13 => 4, 4 => 5, 5 => 6, 6 => 7, 7 => 8, 8 => 9, 10 => 10, 11 => 11]
