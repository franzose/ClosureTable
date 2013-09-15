# ClosureTable 2

Formerly bundle for Laravel 3, now it's a package for Laravel 4. It's intended to use when you need to operate hierarchical data in database. The package is an implementation of a well-known database design pattern called Closure Table. The codebase is being rewritten completely, however, the ClosureTable 2 is as simple in usage as ClosureTable 1 used to be.

## Installation
To install the package, put the following in your composer.json:
<pre>
<code>
"require": {
	"franzose/closure-table": "dev-master"
}
</code>
</pre>

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

You deal with the collection, thus you can control its items as you usually do. Descendants? They are already loaded.
<pre>
<code>
$tree = Page::tree();
$page = $tree->find(15);
$children = $page->children();
$child = $page->childAt(3);
$grandchildren = $page->childAt(3)->children(); //and so on
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
You can customize the following things in your ClosureTable:<br>
1. `position` column name in the entity database table. Just set `const POSITION` of your model to whatever you want.<br>
2. `ancestor` column name in the closure database table. Just set `const ANCESTOR` of your model to whatever you want. The same is for `descendant` and `depth` columns: they have their own constants in the `\Franzose\ClosureTable\Entity` class.
