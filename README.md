# ClosureTable

ClosureTable bundle allows you to easily store and manipulate hierarchical data. For example, categories of something or pages. The bundle is pretty simple, well-documented and easy to use. It has no configuration (except a few lines in the ClosureTable\ClosureTable model), so it's almost a plug-and-play thing. Remember though that you need to create database tables yourself by creating migrations.

# Conventions
## Tree paths table name
ClosureTable bundle incapsulates tree paths manipulation logic in <code>ClosureTable\TreePath</code> model. You **do not** need to extend it and put a new model in, say, <code>application/models</code> directory every time you want to use ClosureTable's abilities. And, fortunately, with <code>ClosureTable\ClosureTable</code> model you can avoid that. Just specify tree paths table name for your entity by setting <code>public static $treepath</code> property in your ClosureTable'd model. For example,

<pre>
<code>
use ClosureTable\ClosureTable;

class Page extends ClosureTable {
    public static $treepath = 'pages_treepath';
...
}
</code>
</pre>

Now <code>ClosureTable\TreePath</code> uses <code>pages_treepath</code> database table.
 
## Foreign key name
ClosureTable bundle uses a feature from Adjacency List pattern to retrieve immediate node's parent. As you know, it requires a parent identifier field (foreign key) in the entity's table. To get or set it, in Laravel you need to call it explicitly. For example, if foreign key name is <code>parent\_id</code>, you write <code>$page->parent\_id = 3</code>. As you could suppose, ClosureTable base model cannot guess what the current name of the foreign key is. Therefore it uses <code>$this->{static::$parent\_key}</code> everywhere it needs to get or set the foreign key.

For you, default value of the foreign key name is already set to <code>parent\_id</code>. If you want to change it, set <code>public static $parent\_key</code> to another value.

# Some Examples
