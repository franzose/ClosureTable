.. index::
   single: Setup your ClosureTable

Setup your ClosureTable
=======================

Create models and migrations
----------------------------

For example, let's assume you're working on pages. You can just use an ``artisan`` command to create models and migrations automatically without preparing all the stuff by hand. Open terminal and put the following:

.. code-block:: bash

	php artisan closuretable:make --entity=page

All options of the command:

1. ``--namespace``, ``-ns`` *[optional]*: namespace for classes, set by ``--entity`` and ``--closure`` options, helps to avoid namespace duplication in those options
2. ``--entity``, ``-e``: entity class name; if namespaced name is used, then the default closure class name will be prepended with that namespace
3. ``--entity-table``, ``-et`` *[optional]*: entity table name
4. ``--closure``, ``-c`` *[optional]*: closure class name
5. ``--closure-table``, ``-ct`` *[optional]*: closure table name
6. ``--models-path``, ``-mdl`` *[optional]*: custom models path
7. ``--migrations-path``, ``-mgr`` *[optional]*: custom migrations path
8. ``--use-innodb`` and ``-i`` *[optional]*: InnoDB migrations have been made optional as well with new paramaters. Setting this will enable the InnoDB engine.

That's almost all, folks! The ‘dummy’ stuff has just been created for you. You will need to add some fields to your entity migration because the created ‘dummy’ includes just **required** ``id``, ``parent_id``, ``position``, and ``real depth`` columns:

1. ``id`` is a regular autoincremented column
2. ``parent_id`` column is used to simplify immediate ancestor querying and, for example, to simplify building the whole tree
3. ``position`` column is used widely by the package to make entities sortable
4. ``real depth`` column is also used to simplify queries and reduce their number

By default, entity’s closure table includes the following columns:

1. **Autoincremented identifier**
2. **Ancestor column** points on a parent node
3. **Descendant column** points on a child node
4. **Depth column** shows a node depth in the tree

It is by closure table pattern design, so remember that you must not delete these four columns.

Remember that many things are made customizable, so see :doc:`Customization<customization>` for more information.