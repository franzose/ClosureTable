# ClosureTable 2

Formerly bundle for Laravel 3, now it's a package for Laravel 4. It's intended to use when you need to operate hierarchical data in database. The package is an implementation of a well-known database design pattern called Closure Table. The codebase is being rewritten completely, however, the ClosureTable 2 is as simple in usage as ClosureTable 1 used to be from the start.

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
    protected static $closure = 'pages_closure';
}
</code>
</pre>

Violà! You have a new Entity. Take a look at the `protected static $closure` variable. It is the name of the closure table where relationships between entities are stored. Remember that you will never have to manually extend the `ClosureTable` model for each your `Entity` model until you want to change the default columns names in the closure table. If you do want, see ‘<a href="#customization">Customization</a>’ section of this readme for more information.

Every Entity uses `position` table column in order to be sortable. You can define your own column name by changing the default value of `Entity::POSITION` constant.

### Create migrations

Please, read the Laravel's ‘<a href="http://laravel.com/docs/migrations">Migrations & Seeding</a>’ first. Keep in mind that you must create two database tables—one for the entities, one for their relationships (closure table). 

Your Entity table must include `position` column, which name is defined by `Entity::POSITION` constant, as I told you above.

Your closure table must include the following columns:
1. **Autoincremented identifier**
2. **Ancestor column** points on a parent node
3. **Descendant column** points on a child node
4. **Depth column** shows a node depth in the tree

Each of their names is customizable. See ‘<a href="#customization">Customization</a>’ section of this readme for more information.

## Time of coding
Once your models and their database tables are created, at last, you can start actually coding. Here I will show you ClosureTable's specific approaches.

### Get ancestors

<pre>
<code>
$page = Page::find(15);
$ancestors = $page->ancestors();

if ($page->hasAncestors())
{
}

$ancestorsNumber = $page->countAncestors();
</code>
</pre>

### Get descendants

<pre>
<code>
$page = Page::find(15);
$descendants = $page->descendants();

if ($page->hasDescendants())
{
}

$descendantsNumber = $page->countDescendants();
</code>
</pre>

### Get direct children

<pre>
<code>
$page = Page::find(15);
$children = $page->children();

if ($page->hasChildren())
{
}

$childrenNumber = $page->countChildren();
</code>
</pre>

### Get siblings

<pre>
<code>
$page = Page::find(15);
$nextOne = $page->nextSibling();
$nextAll = $page->nextSiblings();
$prevOne = $page->prevSibling();
$prevAll = $page->prevSiblings();

if ($page->hasSiblings())
{
}

if ($page->hasPrevSiblings())
{
}

if ($page->hasNextSiblings())
{
}

$siblingsNumber = $page->countSiblings();
$nextNumber = $page->countNextSiblings();
$prevNumber = $page->countPrevSiblings();
</code>
</pre>

### Get the entire tree

<pre>
<code>
$tree = Page::tree();
</code>
</pre>

### Moving entities

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
