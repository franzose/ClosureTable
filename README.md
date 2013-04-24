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

# Migration Tables
Let's say we want to create a structure of pages. Migration classes could be done as follows. First, for the 'pages' table.
<pre>
<code>
<?php

class Create_Pages_Table {

    /**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pages', function($table){
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable(); //required by ClosureTable/ClosureTable model
            $table->string('language', 3);
            $table->boolean('published')->default(false);
            $table->string('url', 255);
            $table->string('title', 255);
            $table->text('content');
            $table->integer('position')->unsigned();
            $table->timestamps();

            $table->index('pubid');
            $table->index('parent_id');
            $table->index('language');
            $table->index('url');
            $table->index('position');
        });
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pages');
	}
</code>
</pre>

And then for the pages' tree paths.
<pre>
<code>
<?php

class Create_Pages_Treepath_Table {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages_treepath', function($table){
            $table->increments('tpid');
            
            //these three fields are required by ClosureTable\TreePath model
            $table->integer('ancestor')->unsigned();
            $table->integer('descendant')->unsigned();
            $table->integer('level')->unsigned();
            
            $table->index('level');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pages_treepath');
    }

}
</code>
</pre>

# Code Examples
## Where is my parent? And grandparents?
Here they are. In a few lines.
<pre>
<code>
$page       = Page::find(10);
$has_parent = $page->has_parent(); 
$parent     = $page->parent()->first(); //or $page->parent;
$parents    = $page->ancestors()->get(); //or $page->ancestors;
</code>
</pre>

## What about my children?
Get them as follows:
<pre>
<code>
$page         = Page::find(10);
$has_children = $page->has_descendants();
$children     = $page->descendants()->get(); //or $page->descendants;
</code>
</pre>

## I want to find friends
Quite simple.
<pre>
<code>
$page = Page::find(10);

//next siblings
$next     = $page->next_siblings; //or $page->siblings();
$next_one = $page->next_sibling;

//previous siblings
$prev     = $page->prev_siblings; //or $page->siblings('all', 'prev');
$prev_one = $page->prev_sibling; //or $page->siblings('one', 'prev');
</code>
</pre>

## I want the full tree
Get it.
<pre>
<code>
//assuming you want pages tree
$tree = Page::fulltree();
</code>
</pre>

## And now I want only root nodes of my menu
Dead simple.
<pre>
<code>
$roots = MenuItem::roots();
$is_root = $menu_item->is_root(); //a check somewhere in your code
$item->make_root(); //makes an item the root
</code>
</pre>

## I want to move my item (with all the children!).
With ease.
<pre>
<code>
$item = MenuItem::find(15);
$item->move_to(MenuItem::find(10));

//or if you want to set the position that differs from 0 (i.e. first)
$item->move_to(MenuItem::find(10), 5); //will be sixth
</code>
</pre>

## Need a child.
Use shorthand method or the default one.
<pre>
<code>
$item->append_child(new MenuItem(/* attributes */)); //or $item->descendants()->insert(new MenuItem(/* attributes */));
</code>
</pre>

## And once I got rid of it.
Just remove.
<pre>
<code>
$item->remove_child($position);
</code>
</pre>

## Burn them.
Ashes.
<pre>
<code>
$item->delete_with_subtree();
</code>
</pre>
