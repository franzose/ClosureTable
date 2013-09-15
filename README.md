# ClosureTable 2

Formerly bundle for Laravel 3, now it's a package for Laravel 4. It's intended to use when you need to operate hierarchical data in database. The package is an implementation of a well-known database design pattern called Closure Table. The codebase is being rewritten completely, however, the ClosureTable 2 is as simple in usage as ClosureTable 1 used to be.

## Installation

## Setup your ClosureTable
### Create the Entity model
For example, let's assume you're working on pages. In `app/models`, create new file called `Page.php` and put the following into it:

<pre>
<code>
&lt;?php

use \Franzose\ClosureTable\Entity;

class Page extends Entity {
    protected $table = 'pages';
    protected $closure = 'pages_closure';
    protected $fillable = array('title', 'excerpt', 'content');
}
</code>
</pre>

Violà! You have a new `Entity`. Take a look at the `protected $closure` property. It is the name of the closure table where relationships between `Entities` are stored. There is no separate model for the closure database table anymore. See ‘<a href="#customization">Customization</a>’ for more information.

### Create migrations

Open terminal and put the following commands:
<pre>
<code>
php artisan migrate:make create_pages_table --table=pages --create
php artisan migrate:make create_pages_closure_table --table=pages_closure --create
</code>
</pre>

Your `pages` table schema should look like this:
<pre>
<code>
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
</code>
</pre>

Your `Entity` table must include `position` column in order to be sortable. The name of the column can be <a href="#customization">customized</a>.

Your `pages_closure` table schema should look like this:
<pre>
<code>
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
</code>
</pre>

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
<pre>
<code>
$parent = Page::find(15)->parent();
</code>
</pre>

### Ancestors
<pre>
<code>
$page = Page::find(15);
$ancestors = $page->ancestors();
$hasAncestors = $page->hasAncestors();
$ancestorsNumber = $page->countAncestors();
</code>
</pre>

### Direct descendants (children)
<pre>
<code>
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
</code>
</pre>

### Descendants
<pre>
<code>
$page = Page::find(15);
$descendants = $page->descendants();
$hasDescendants = $page->hasDescendants();
$descendantsNumber = $page->countDescendants();
</code>
</pre>

### Siblings
<pre>
<code>
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
</code>
</pre>

### Roots (entities that have no ancestors)
<pre>
<code>
$roots = Page::roots();
$isRoot = Page::find(23)->isRoot();
Page::find(11)->makeRoot();
</code>
</pre>

### Entire tree
<pre>
<code>
$tree = Page::tree();
</code>
</pre>

### Moving
<pre>
<code>
$page = Page::find(25);
$page->moveTo(Page::find(14));

//or to a certain position within the subtree
$page->moveTo(Page::find(14), 5);

//another way of moving
Page::moveGivenTo($page, Page::find(14), 5);
</code>
</pre>

### Deleting subtree
If you don't use foreign keys for some reason, you can delete subtree manually. This will delete the page and all its descendants:
<pre>
<code>
$page = Page::find(34);
$page->deleteSubtree();
</code>
</pre>

## Customization


## Changes compared to ClosureTable 1
### Restriction on columns names removed
ClosureTable 1 had hardcoded columns names of the closure table: `ancestor`, `descendant`, `level`. In addition to this, `ClosureTable` model used Adjacency List feature with direct parent identifier in the entity table (i.e. `parent_id` or the like, which column name you had to set manually if needed) and hardcoded `position` column.

Now all that stuff is obsolete. `ClosureTable` model has three constants for you to define column names you want:
1. `ClosureTable::ANCESTOR` is the ancestor column name, `ancestor` by default
2. `ClosureTable::DESCENDANT` is the descendant column name, `descendant` by default
3. `ClosureTable::DEPTH` is the depth column name, `depth` by default

All you need to do is to extend the base `ClouseTable` model and define column names you want in your new model. Remember that if you extend `ClosureTable` model you must change its class name in `closuretable()` method of your `Entity` model to properly setup relationship between your `Entity` and `ClosureTable` models.

Also `Entity` model doesn't use the feature from Adjacency List anymore (however a trick with direct parent presents) and has constant `Entity::POSITION` for you to define position column name you want.

### Closure table name
As the ClosureTable 1, this package offers you to define the name of the closure table in the Entity model. This is done to avoid useless spawning of `ClosuseTable` models. Instead of that, you write the name of the closure table to `Entity::$closure` (formerly `ClosureTable::$treepath`) once, and your Entity will use the same `ClosureTable` model as others but with different database table. For example,

<pre>
<code>
use \Franzose\ClosureTable\Entity;

class Page extends Entity {
    public static $closure = 'pages_closure';
}
</code>
</pre>
