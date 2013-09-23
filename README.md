# ClosureTable 2
[![Latest Stable Version](https://poser.pugx.org/franzose/closure-table/v/stable.png)](https://packagist.org/packages/franzose/closure-table)
[![Total Downloads](https://poser.pugx.org/franzose/closure-table/downloads.png)](https://packagist.org/packages/franzose/closure-table)

Formerly bundle for Laravel 3, now it's a package for Laravel 4. It's intended to use when you need to operate hierarchical data in database. The package is an implementation of a well-known database design pattern called Closure Table. The codebase is being rewritten completely, however, the ClosureTable 2 is as simple in usage as ClosureTable 1 used to be.

## Installation
To install the package, put the following in your composer.json:

```json
"require": {
	"franzose/closure-table": "dev-master"
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
### Create the Entity model
For example, let's assume you're working on pages. In `app/models`, create new file called `Page.php` and put the following into it:

```php
<?php

use \Franzose\ClosureTable\Entity;

class Page extends Entity {
    protected $fillable = array('title', 'excerpt', 'content');
}
```

Violà! You have a new `Entity`. Closure table name is set by `protected $closure` property. See ‘<a href="#customization">Customization</a>’ for more information.

### Create migrations

Open terminal and put the following commands:

```bash
php artisan migrate:make create_pages_table --table=pages --create
php artisan migrate:make create_pages_closure_table --table=pages_closure --create
```

Your `pages` table schema should look like this:

```php
public function up()
{
	Schema::create('pages', function(Blueprint $table)
	{
		$table->increments('id');
        $table->string('title');
        $table->string('excerpt', 500);
        $table->longText('content');
        $table->integer('position', false, true); //unsigned
		$table->timestamps();
        $table->softDeletes(); //notice this.
	});
}
```

Your `Entity` table must include `position` column in order to be sortable. The name of the column can be <a href="#customization">customized</a>.

Your `pages_closure` table schema should look like this:

```php
public function up()
{
	Schema::create('pages_closure', function(Blueprint $table)
	{
		$table->increments('id');
        $table->integer('ancestor', false, true); //unsigned
        $table->integer('descendant', false, true);
        $table->integer('depth', false, true);

        $table->foreign('ancestor')->references('id')->on('pages')->onDelete('cascade');
        $table->foreign('descendant')->references('id')->on('pages')->onDelete('cascade');
        $table->index('depth');
	});
}
```

Your closure table must include the following columns:<br>
1. **Autoincremented identifier**<br>
2. **Ancestor column** points on a parent node<br>
3. **Descendant column** points on a child node<br>
4. **Depth column** shows a node depth in the tree

Each of their names is customizable. See ‘<a href="#customization">Customization</a>’ for more information.

We made foreign keys `cascade` to simplify removing a subtree from the database. That's why the `Entity` model is `softDelete`d by default: we prevent accidential subtree removing that way.

## Time of coding
Once your models and their database tables are created, at last, you can start actually coding. Here I will show you ClosureTable's specific approaches.

### Direct ancestor (parent)

```php
$parent = Page::find(15)->parent();
```

### Ancestors

```php
$page = Page::find(15);
$ancestors = $page->ancestors();
$hasAncestors = $page->hasAncestors();
$ancestorsNumber = $page->countAncestors();
```

### Direct descendants (children)

```php
$page = Page::find(15);
$children = $page->children();
$hasChildren = $page->hasChildren();
$childrenNumber = $page->countChildren();

$newChild = new Page(array(
	'title' => 'The title',
	'excerpt' => 'The excerpt',
	'content' => 'The content of a child'
));

$page->appendChild($newChild);

//or you could get that child after appending
//second argument is the position
//if null, it will be set automatically
$child = $page->appendChild($newChild, null, true);

$page->removeChild(0);
```

### Descendants

```php
$page = Page::find(15);
$descendants = $page->descendants();
$hasDescendants = $page->hasDescendants();
$descendantsNumber = $page->countDescendants();
```

### Siblings

```php
$page  = Page::find(15);
$first = $page->firstSibling(); //or $page->siblingAt(0);
$last  = $page->lastSibling();
$atpos = $page->siblingAt(5);

$prevOne = $page->prevSibling();
$prevAll = $page->prevSiblings();
$prevsFromPos = $page->prevSiblings(5); //previous siblings from position 5
$hasPrevs = $page->hasPrevSiblings();
$prevsNumber = $page->countPrevSiblings();

$nextOne = $page->nextSibling();
$nextAll = $page->nextSiblings();
$nextFromPos = $page->nextSiblings(10);
$hasNext = $page->hasNextSiblings();
$nextNumber = $page->countNextSiblings();

//in both directions
$hasSiblings = $page->hasSiblings();
$siblingsNumber = $page->countSiblings();
```

### Roots (entities that have no ancestors)

```php
$roots = Page::roots();
$isRoot = Page::find(23)->isRoot();
Page::find(11)->makeRoot();
```

### Entire tree

```php
$tree = Page::tree();
```

You deal with the collection, thus you can control its items as you usually do. Descendants? They are already loaded.

```php
$tree = Page::tree();
$page = $tree->find(15);
$children = $page->children();
$child = $page->childAt(3);
$grandchildren = $page->childAt(3)->children(); //and so on
```

### Moving

```php
$page = Page::find(25);
$page->moveTo(Page::find(14));

//or to a certain position within the subtree
$page->moveTo(Page::find(14), 5);

//another way of moving
Page::moveGivenTo($page, Page::find(14), 5);
```

### Deleting subtree
If you don't use foreign keys for some reason, you can delete subtree manually. This will delete the page and all its descendants:

```php
$page = Page::find(34);
$page->deleteSubtree();
```

## Customization
You can customize the following things in your ClosureTable:<br>
1. `Entity` table name. Set `protected $table` to change it.<br>
2. Closure table name. By default its name is `Entity` table name + `_closure` (e.g. `pages_closure`). Set `protected $closure` if you want to change closure table name.<br>
3. `position` column name in the entity database table. Just set `const POSITION` of your model to whatever you want.<br>
4. `ancestor` column name in the closure database table. Just set `const ANCESTOR` of your model to whatever you want. The same is for `descendant` and `depth` columns: they have their own constants in the `\Franzose\ClosureTable\Entity` class.
