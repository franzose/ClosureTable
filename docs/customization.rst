.. index::
   single: Customization

Customization
=============

You can customize the default things in your classes created by the ClosureTable ``artisan`` command:

1. **Entity table name**: change ``protected $table`` property
2. **Closure table name**: do the same in your ``ClosureTable`` (e.g. ``PageClosure``)
3. **Entity's ``parent_id``, ``position``, and ``real depth`` column names**: change return values of ``getParentIdColumn()``, ``getPositionColumn()``, and ``getRealDepthColumn()`` respectively
4. **Closure table's ``ancestor``, ``descendant``, and ``depth`` columns names**: change return values of ``getAncestorColumn()``, ``getDescendantColumn()``, and ``getDepthColumn()`` respectively.