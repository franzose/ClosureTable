# ClosureTable
[![Build Status](https://travis-ci.org/franzose/ClosureTable.png)](https://travis-ci.org/franzose/ClosureTable)
[![Latest Stable Version](https://poser.pugx.org/franzose/closure-table/v/stable.png)](https://packagist.org/packages/franzose/closure-table)
[![Total Downloads](https://poser.pugx.org/franzose/closure-table/downloads.png)](https://packagist.org/packages/franzose/closure-table)

## Branches
1. <strong>L4</strong> supports Laravel 4
2. <strong>L5.1</strong> supports Laravel < 5.2
3. <strong>L5.3</strong> supports Laravel 5.2-5.3
4. <strong>L5.4</strong> supports Laravel 5.4
5. <strong>master</strong> is for any actual Laravel version, so be careful

Hi, this is a database package for Laravel. It's intended to use when you need to operate hierarchical data in database. The package is an implementation of a well-known database design pattern called Closure Table. The package includes generators for models and migrations.

## Installation
To install the package, put the following in your composer.json:

```json
"require": {
	"franzose/closure-table": "4.*"
}
```

And to `app/config/app.php`:
```php
'providers' => array(
        // ...
        'Franzose\ClosureTable\ClosureTableServiceProvider',
    ),
```

## Setup your ClosureTable
### Create models and migrations
For example, let's assume you're working on pages. You can just use an `artisan` command to create models and migrations automatically without preparing all the stuff by hand. Open terminal and put the following:

```bash
php artisan closuretable:make --entity=page
```

All options of the command:<br>
1. `--namespace`, `-ns` _[optional]_: namespace for classes, set by `--entity` and `--closure` options, helps to avoid namespace duplication in those options<br>
2. `--entity`, `-e`: entity class name; if namespaced name is used, then the default closure class name will be prepended with that namespace<br>
3. `--entity-table`, `-et` _[optional]_: entity table name<br>
4. `--closure`, `-c` _[optional]_: closure class name<br>
5. `--closure-table` _[optional]_, `-ct`: closure table name<br>
6. `--models-path`, `-mdl` _[optional]_: custom models path<br>
7. `--migrations-path`, `-mgr` _[optional]_: custom migrations path<br>
8.  `--use-innodb` and `-i` _[optional]_: InnoDB migrations have been made optional as well with new paramaters. Setting this will enable the InnoDB engine.

That's almost all, folks! The ‘dummy’ stuff has just been created for you. You will need to add some fields to your entity migration because the created ‘dummy’ includes just **required** `id`, `parent_id`, `position`, and `real depth` columns:<br>

1. **`id`** is a regular autoincremented column<br>
2. **`parent_id`** column is used to simplify immediate ancestor querying and, for example, to simplify building the whole tree<br>
3. **`position`** column is used widely by the package to make entities sortable<br>
4. **`real depth`** column is also used to simplify queries and reduce their number

By default, entity’s closure table includes the following columns:<br>
1. **Autoincremented identifier**<br>
2. **Ancestor column** points on a parent node<br>
3. **Descendant column** points on a child node<br>
4. **Depth column** shows a node depth in the tree

It is by closure table pattern design, so remember that you must not delete these four columns.

Remember that many things are made customizable, so see ‘<a href="#customization">Customization</a>’ for more information.

## Time of coding
Once your models and their database tables are created, at last, you can start actually coding. Here I will show you ClosureTable's specific approaches.

### Direct ancestor (parent)

```php
$parent = Page::find(15)->getParent();
```

### Ancestors

```php
$page = Page::find(15);
$ancestors = $page->getAncestors();
$ancestors = $page->getAncestorsTree(); // Tree structure
$ancestors = $page->getAncestorsWhere('position', '=', 1);
$hasAncestors = $page->hasAncestors();
$ancestorsNumber = $page->countAncestors();
```

### Direct descendants (children)

```php
$page = Page::find(15);
$children = $page->getChildren();
$hasChildren = $page->hasChildren();
$childrenNumber = $page->countChildren();

$newChild = new Page(array(
	'title' => 'The title',
	'excerpt' => 'The excerpt',
	'content' => 'The content of a child'
));

$newChild2 = new Page(array(
	'title' => 'The title',
	'excerpt' => 'The excerpt',
	'content' => 'The content of a child'
));

$page->addChild($newChild);

//you can set child position
$page->addChild($newChild, 5);

//you can get the child
$child = $page->addChild($newChild, null, true);

$page->addChildren([$newChild, $newChild2]);

$page->getChildAt(5);
$page->getFirstChild();
$page->getLastChild();
$page->getChildrenRange(0, 2);

$page->removeChild(0);
$page->removeChild(0, true); //force delete
$page->removeChildren(0, 3);
$page->removeChildren(0, 3, true); //force delete
```

### Descendants

```php
$page = Page::find(15);
$descendants = $page->getDescendants();
$descendants = $page->getDescendantsWhere('position', '=', 1);
$descendantsTree = $page->getDescendantsTree();
$hasDescendants = $page->hasDescendants();
$descendantsNumber = $page->countDescendants();
```

### Siblings

```php
$page  = Page::find(15);
$first = $page->getFirstSibling(); //or $page->getSiblingAt(0);
$last  = $page->getLastSibling();
$atpos = $page->getSiblingAt(5);

$prevOne = $page->getPrevSibling();
$prevAll = $page->getPrevSiblings();
$hasPrevs = $page->hasPrevSiblings();
$prevsNumber = $page->countPrevSiblings();

$nextOne = $page->getNextSibling();
$nextAll = $page->getNextSiblings();
$hasNext = $page->hasNextSiblings();
$nextNumber = $page->countNextSiblings();

//in both directions
$hasSiblings = $page->hasSiblings();
$siblingsNumber = $page->countSiblings();

$sibligns = $page->getSiblingsRange(0, 2);

$page->addSibling(new Page);
$page->addSibling(new Page, 3); //third position

//add and get the sibling
$sibling = $page->addSibling(new Page, null, true);

$page->addSiblings([new Page, new Page]);
$page->addSiblings([new Page, new Page], 5); //insert from fifth position
```

### Roots (entities that have no ancestors)

```php
$roots = Page::getRoots();
$isRoot = Page::find(23)->isRoot();
Page::find(11)->makeRoot(0); //at the moment we always have to set a position when making node a root
```

### Entire tree

```php
$tree = Page::getTree();
$treeByCondition = Page::getTreeWhere('position', '>=', 1);
```

You deal with the collection, thus you can control its items as you usually do. Descendants? They are already loaded.

```php
$tree = Page::getTree();
$page = $tree->find(15);
$children = $page->getChildren();
$child = $page->getChildAt(3);
$grandchildren = $page->getChildAt(3)->getChildren(); //and so on
```

### Moving

```php
$page = Page::find(25);
$page->moveTo(0, Page::find(14));
$page->moveTo(0, 14);
```

### Deleting subtree
If you don't use foreign keys for some reason, you can delete subtree manually. This will delete the page and all its descendants:

```php
$page = Page::find(34);
$page->deleteSubtree();
$page->deleteSubtree(true); //with subtree ancestor
$page->deleteSubtree(false, true); //without subtree ancestor and force delete
```

## Customization
You can customize default things in your own classes created by the ClosureTable `artisan` command:<br>
1. **Entity table name**: change `protected $table` property<br>
2. **Closure table name**: do the same in your `ClosureTable` (e.g. `PageClosure`)<br>
3. **Entity's `parent_id`, `position`, and `real depth` column names**: change return values of `getParentIdColumn()`, `getPositionColumn()`, and `getRealDepthColumn()` respectively<br>
4. **Closure table's `ancestor`, `descendant`, and `depth` columns names**: change return values of `getAncestorColumn()`, `getDescendantColumn()`, and `getDepthColumn()` respectively.
