# ClosureTable
[![GitHub Workflow Status](https://github.com/franzose/ClosureTable/actions/workflows/tests.yml/badge.svg)](https://github.com/franzose/ClosureTable/actions)
[![Latest Release](https://img.shields.io/github/v/release/franzose/ClosureTable)](https://packagist.org/packages/franzose/closure-table)
[![Total Downloads](https://poser.pugx.org/franzose/closure-table/downloads.png)](https://packagist.org/packages/franzose/closure-table)

This is a database manipulation package for the Laravel framework. You may want to use it when you need to store and operate hierarchical data in your database. The package is an implementation of a well-known design pattern called [closure table](https://www.slideshare.net/billkarwin/models-for-hierarchical-data). However, in order to simplify and optimize SQL `SELECT` queries, it uses adjacency lists to query direct parent/child relationships.

## Installation
It's strongly recommended to use [Composer](https://getcomposer.org) to install the package:
```bash
$ composer require franzose/closure-table
```

## Setup
Create the entity model, closure table model, and their migration manually. You can use the [examples](examples) as a starting point:
- [examples/Node.php](examples/Node.php)
- [examples/NodeClosure.php](examples/NodeClosure.php)
- [examples/2020_01_01_000000_create_nodes_table_migration.php](examples/2020_01_01_000000_create_nodes_table_migration.php)

## Requirements
Laravel 9+ and PHP 8.2+ are required. Keep in mind that, by design of this package, the models/tables have a required minimum of attributes/columns:
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

## Model Scopes
Since ClosureTable 6, a lot of query scopes are available in the Entity model. Since ClosureTable 7, they have been moved into separate traits:
1. [Ancestor](src/Scope/Ancestor.php)
2. [Descendant](src/Scope/Descendant.php)
3. [DirectChild](src/Scope/DirectChild.php)
4. [Sibling](src/Scope/Sibling.php)

Learn how to use query scopes in the [Laravel documentation](https://laravel.com/docs/12.x/eloquent#query-scopes). Dig into the [examples](examples) to see how to use them and more.

### Ordering
`getAncestors()` and `getDescendants()` are ordered by depth ascending (nearest first), then by `position` ascending, and finally by the primary key ascending as a stable fallback. For scope queries, add your own `orderBy()` (or `reorder()`) to define the ordering you want.
