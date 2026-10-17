# Upgrade Guide

## Upgrading from 6.x to 7.0

### Requirements
ClosureTable 7 requires PHP 8.2+ and Laravel 12+.

### Namespaces and class names
The `Models`, `Extensions` and `Contracts` namespaces are gone. Update your imports:

```diff
-use Franzose\ClosureTable\Models\Entity;
-use Franzose\ClosureTable\Models\ClosureTable;
-use Franzose\ClosureTable\Extensions\Collection;
+use Franzose\ClosureTable\Entity;
+use Franzose\ClosureTable\ClosureTable;
+use Franzose\ClosureTable\EntityCollection;
```

`EntityInterface` and `ClosureTableInterface` have been removed. Drop them from your `implements` clauses and type hints, and use `Entity` and `ClosureTable` instead.

### Service provider and generators
The service provider, the `closuretable:make` artisan command and the model/migration generators have been removed. If you registered `Franzose\ClosureTable\ClosureTableServiceProvider` manually (e.g. in `config/app.php` or `bootstrap/providers.php`), remove it.

Create models and migrations by hand. The [examples](examples) directory contains a starting point.

### Removed methods
The methods deprecated since ClosureTable 6 have been removed:

```diff
-Node::find(4)->getAncestorsTree();
+Node::find(4)->getAncestors()->toTree();

-Node::find(4)->getAncestorsWhere('id', '>', 1);
+Node::find(4)->ancestors()->where('id', '>', 1)->get();

-Node::find(4)->getDescendantsTree();
+Node::find(4)->getDescendants()->toTree();

-Node::find(4)->getDescendantsWhere('foo', '=', 'bar');
+Node::find(4)->descendants()->where('foo', '=', 'bar')->get();

-Node::getTree();
+Node::all()->toTree();

-Node::getTreeWhere('foo', '=', 'bar');
-Node::getTreeByQuery(Node::where('foo', '=', 'bar'));
+Node::where('foo', '=', 'bar')->get()->toTree();
```

The following methods, which were internal, have been removed as well: `appendChild()` (use `addChild()`), `hasChildrenRelation()`, `getChildrenRelationIndex()`, `getRealDepthColumn()` and `getQualifiedRealDepthColumn()`.

### Query scopes
The query scopes have moved from `Entity` into the traits `Scope\Ancestor`, `Scope\Descendant`, `Scope\DirectChild` and `Scope\Sibling`. `Entity` uses all of them, so calling code does not change. If you override a scope in your model, the method signature must match the trait.

### Typed signatures
Public and protected methods of `Entity`, `ClosureTable` and `EntityCollection` now have parameter and return types. If your models override any of them (for example `getParentIdColumn()`, `getPositionColumn()` or `getDepthColumn()`), add the matching types:

```diff
-public function getPositionColumn()
+public function getPositionColumn(): string
```

### Depth attribute
Entities now have a `depth` attribute that returns the node's nesting level (0 for roots). It is backed by the `closureDepth` relation, which is eager loaded via `$with` on every query. Note that:

- if your entity table has its own `depth` column, the accessor shadows it;
- if your model overrides `$with`, add `'closureDepth'` to it, otherwise every access to `depth` runs a separate query.

### Behaviour changes
- `getAncestors()` and `getDescendants()` return nodes in a deterministic order: by depth, then position, then primary key.
- When an entity is created with an explicit `position`, that value is stored as given (gaps included); only moves and updates clamp the position to the number of siblings. Without an explicit position, a new entity is appended after its last sibling.
