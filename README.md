# ClosureTable 2

Formerly bundle for Laravel 3, now it's a package for Laravel 4. It's intended to use when you need to operate hierarchical data in database. The package is an implementation of a well-known database design pattern called Closure Table. The codebase is being rewritten completely, however, the ClosureTable 2 is as simple in usage as ClosureTable 1 used to be from the start.

# Installation

# Changes compared to ClosureTable 1
## Model names
I decided to rename models and give them more appropriate names. Former `ClosureTable` model is now `Entity` because the database table, it operates, contains the entity data only. `TreePath` is now `ClosureTable`, named after the pattern, as it contains relationships of entities to each other.

## Restriction on columns names removed
ClosureTable 1 had hardcoded columns names of the closure table: `ancestor`, `descendant`, `level`. In addition to this, `ClosureTable` model used Adjacency List feature with direct parent identifier in the entity table (i.e. `parent_id` or the like, which column name you had to set manually if needed) and hardcoded `position` column.

Now all that stuff is obsolete. `ClosureTable` model has three constants for you to define column names you want:
1. `ClosureTable::ANCESTOR` is the ancestor column name, `ancestor` by default
2. `ClosureTable::DESCENDANT` is the descendant column name, `descendant` by default
3. `ClosureTable::DEPTH` is the depth column name, `depth` by default

All you need to do is to extend the base `ClouseTable` model and define column names you want in your new model. Remember that if you extend `ClosureTable` model you must change its class name in `closuretable()` method of your `Entity` model to properly setup relationship between your `Entity` and `ClosureTable` models.

Also `Entity` model doesn't use the feature from Adjacency List anymore (however a trick with direct parent presents) and has constant `Entity::POSITION` for you to define position column name you want.

## Closure table name
As the ClosureTable 1, this package offers you to define the name of the closure table in the Entity model. This is done to avoid useless spawning of `ClosuseTable` models. Instead of that, you write the name of the closure table to `Entity::$closure` (formerly `ClosureTable::$treepath`) once, and your Entity will use the same `ClosureTable` model as others but with different database table. For example,

<pre>
<code>
use \Franzose\ClosureTable\Entity;

class Page extends Entity {
    public static $closure = 'pages_closure';
}
</code>
</pre>

# Migration tables
