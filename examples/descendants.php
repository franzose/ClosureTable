<?php
declare(strict_types=1);

namespace App;

$nodes = [
    new Node(['id' => 1]),
    new Node(['id' => 2, 'parent_id' => 1]),
    new Node(['id' => 3, 'parent_id' => 2]),
    new Node(['id' => 4, 'parent_id' => 3])
];

foreach ($nodes as $node) {
    $node->save();
}

Node::find(1)->getDescendants()->pluck('id')->toArray(); // [2, 3, 4]
Node::find(1)->countDescendants(); // 3
Node::find(1)->hasDescendants(); // true
Node::find(1)->descendants()->where('id', '<', 4)->get()->pluck('id')->toArray(); // [2, 3];
Node::find(1)->descendantsWithSelf()->where('id', '<', 4)->get()->pluck('id')->toArray(); // [1, 2, 3];
Node::descendantsOf(1)->where('id', '<', 4)->get()->pluck('id')->toArray(); // [2, 3];
Node::descendantsWithSelfOf(1)->where('id', '<', 4)->get()->pluck('id')->toArray(); // [1, 2, 3];
Node::find(4)->getDescendants()->toTree();
