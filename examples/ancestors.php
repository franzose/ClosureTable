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

Node::find(4)->getAncestors()->pluck('id')->toArray(); // [3, 2, 1]
Node::find(4)->countAncestors(); // 3
Node::find(4)->hasAncestors(); // true
Node::find(4)->ancestors()->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3];
Node::find(4)->ancestorsWithSelf()->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3, 4];
Node::ancestorsOf(4)->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3];
Node::ancestorsWithSelfOf(4)->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3, 4];
Node::find(4)->getAncestors()->toTree();
