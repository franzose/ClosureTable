.. index::
   single: Customization

Customization
=============

You can customize the default things in your classes created by the ClosureTable ``artisan`` command:

1. **Entity table name** by changing ``protected $table`` of your own ``Entity`` (e.g. ``Page``)
2. **Closure table name** by changing ``protected $table`` of your own ``ClosureTable`` (e.g. ``PageClosure``)
3. ``parent_id``, ``position``, and ``real depth`` column names by changing ``const PARENT_ID``, ``const POSITION``, and ``const REAL_DEPTH`` of your own ``EntityInterface`` (e.g. ``PageInterface``) respectively
4. ``ancestor``, ``descendant``, and ``depth`` columns names by changing ``const ANCESTOR``, ``const DESCENDANT``, and ``const DEPTH`` of your own ``ClosureTableInterface`` (e.g. ``PageClosureInterface``) respectively.