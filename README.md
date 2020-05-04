# ClosureTable
[![Build Status](https://travis-ci.org/franzose/ClosureTable.png)](https://travis-ci.org/franzose/ClosureTable)
[![Latest Release](https://img.shields.io/github/v/release/franzose/ClosureTable)](https://packagist.org/packages/franzose/closure-table)
[![Total Downloads](https://poser.pugx.org/franzose/closure-table/downloads.png)](https://packagist.org/packages/franzose/closure-table)

This is a database manipulation package for the Laravel 5.4+ framework. You may want to use it when you need to store and operate hierarchical data in your database. The package is an implementation of a well-known design pattern called [closure table](https://www.slideshare.net/billkarwin/models-for-hierarchical-data). However, in order to simplify and optimize SQL `SELECT` queries, it uses adjacency lists to query direct parent/child relationships.

Contents:
- [Installation](#installation)
- [Setup](#setup)
- [Requirements](#requirements)
- Examples → [List of Scopes](#scopes)
- Examples → [Parent/Root](#parentroot)
- Examples → [Ancestors](#ancestors)
- Examples → [Descendants](#descendants)
- Examples → [Children](#children)
- Examples → [Siblings](#siblings)
- Examples → [Tree](#tree)
- Examples → [Collection Methods](#collection-methods)


## Installation
It's strongly recommended to use [Composer](https://getcomposer.org) to install the package:
```bash
$ composer require franzose/closure-table
```

If you use Laravel 5.5+, the package's service provider is automatically registered for you thanks to the [package auto-discovery](https://laravel.com/docs/7.x/packages#package-discovery) feature. Otherwise, you have to manually add it to your `config/app.php`:
```php
<?php

return [
    'providers' => [
        Franzose\ClosureTable\ClosureTableServiceProvider::class
    ]
];
```

## Setup
In a basic scenario, you can simply run the following command:
```bash
$ php artisan closuretable:make Node
```
Where `Node` is the name of the entity model. This is what you get from running the above:<br>
1. Two models in the `app` directory: `App\Node` and `App\NodeClosure`<br>
2. A new migration in the `database/migrations` directory

As you can see, the command requires a single argument, name of the entity model. However, it accepts several options in order to provide some sort of customization:
 Option          | Alias | Meaning 
 ----------------| ------| -------
 namespace       | ns    | Custom namespace for generated models. Keep in mind that the given namespace will override  model namespaces: `php artisan closuretable:make Foo\\Node --namespace=Qux --closure=Bar\\NodeTree` will generate `Qux\Node` and `Qux\NodeTree` models.
 entity-table    | et    | Database table name for the entity model       
 closure         | c     | Class name for the closure model
 closure-table   | ct    | Database table name for the closure model
 models-path     | mdl   | Directory in which to put generated models
 migrations-path | mgr   | Directory in which to put generated migrations
 use-innodb      | i     | This flag will tell the generator to set database engine to InnoDB. Useful only if you use MySQL

## Requirements
You have to keep in mind that, by design of this package, the models/tables have a required minimum of attributes/columns:
<table>
<tr>
<th colspan="3">Entity</th>
</tr>
<tr>
<th>Attribute/Column</th>
<th>Customized by</th>
<th>Meaning</th>
</tr>
<tr>
<td>parent_id</td>
<td><code>Entity::getParentIdColumn()</code></td>
<td>ID of the node's immediate parent, simplifies queries for immediate parent/child nodes.</td>
</tr>
<tr>
<td>position</td>
<td><code>Entity::getPositionColumn()</code></td>
<td>Node position, allows to order nodes of the same depth level</td>
</tr>
<tr>
<th colspan="3">ClosureTable</th>
</tr>
<tr>
<th>Attribute/Column</th>
<th>Customized by</th>
<th>Meaning</th>
</tr>
<tr>
<td>id</td>
<td></td>
<td></td>
</tr>
<tr>
<td>ancestor</td>
<td><code>ClosureTable::getAncestorColumn()</code></td>
<td>Parent (self, immediate, distant) node ID</td>
</tr>
<tr>
<td>descendant</td>
<td><code>ClosureTable::getDescendantColumn()</code></td>
<td>Child (self, immediate, distant) node ID</td>
</tr>
<tr>
<td>depth</td>
<td><code>ClosureTable::getDepthColumn()</code></td>
<td>Current nesting level, 0+</td>
</tr>
</table>

## Examples
In the examples, let's assume that we've set up a `Node` model which extends the `Franzose\ClosureTable\Models\Entity` model.

### Scopes
Since ClosureTable 6, a lot of query scopes have become available in the Entity model:

```php
ancestors()
ancestorsOf($id)
ancestorsWithSelf()
ancestorsWithSelfOf($id)
descendants()
descendantsOf($id)
descendantsWithSelf()
descendantsWithSelfOf($id)
childNode()
childNodeOf($id)
childAt(int $position)
childOf($id, int $position)
firstChild()
firstChildOf($id)
lastChild()
lastChildOf($id)
childrenRange(int $from, int $to = null)
childrenRangeOf($id, int $from, int $to = null)
sibling()
siblingOf($id)
siblings()
siblingsOf($id)
neighbors()
neighborsOf($id)
siblingAt(int $position)
siblingOfAt($id, int $position)
firstSibling()
firstSiblingOf($id)
lastSibling()
lastSiblingOf($id)
prevSibling()
prevSiblingOf($id)
prevSiblings()
prevSiblingsOf($id)
nextSibling()
nextSiblingOf($id)
nextSiblings()
nextSiblingsOf($id)
siblingsRange(int $from, int $to = null)
siblingsRangeOf($id, int $from, int $to = null)
```

You can learn how to use query scopes from the [Laravel documentation](https://laravel.com/docs/7.x/eloquent#query-scopes).

### Parent/Root
```php
<?php
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
```

### Ancestors
```php
<?php
$nodes = [
    new Node(['id' => 1]),
    new Node(['id' => 2, 'parent_id' => 1]),
    new Node(['id' => 3, 'parent_id' => 2]),
    new Node(['id' => 4, 'parent_id' => 3])
];

foreach ($nodes as $node) {
    $node->save();
}

Node::find(4)->getAncestors()->pluck('id')->toArray(); // [1, 2, 3]
Node::find(4)->countAncestors(); // 3
Node::find(4)->hasAncestors(); // true
Node::find(4)->ancestors()->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3];
Node::find(4)->ancestorsWithSelf()->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3, 4];
Node::ancestorsOf(4)->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3];
Node::ancestorsWithSelfOf(4)->where('id', '>', 1)->get()->pluck('id')->toArray(); // [2, 3, 4];
```

There are several methods that have been deprecated since ClosureTable 6:

```diff
-Node::find(4)->getAncestorsTree();
+Node::find(4)->getAncestors()->toTree();

-Node::find(4)->getAncestorsWhere('id', '>', 1);
+Node::find(4)->ancestors()->where('id', '>', 1)->get();
```

### Descendants
```php
<?php
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
```

There are several methods that have been deprecated since ClosureTable 6:

```diff
-Node::find(4)->getDescendantsTree();
+Node::find(4)->getDescendants()->toTree();

-Node::find(4)->getDescendantsWhere('foo', '=', 'bar');
+Node::find(4)->descendants()->where('foo', '=', 'bar')->get();
```

### Children
```php
<?php
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
```

### Siblings
```php
<?php
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
```

### Tree
```php
<?php
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
```

There are several methods that have been deprecated since ClosureTable 6:

```diff
-Node::getTree();
-Node::getTreeByQuery(...);
-Node::getTreeWhere('foo', '=', 'bar');
+Node::where('foo', '=', 'bar')->get()->toTree();
```

### Collection methods
This library uses an extended collection class which offers some convenient methods:

```php
<?php
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

/** @var Franzose\ClosureTable\Extensions\Collection $children */
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
```