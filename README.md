# ClosureTable 2

Formerly bundle for Laravel 3, now it's a package for Laravel 4. It's intended to use when you need to operate hierarchical data in database. The package is a implementation of a well-known database design pattern called Closure Table. The codebase is being rewritten completely, however, the ClosureTable 2 is as simple in usage as ClosureTable 1 used to be from the start.

# Changes
## Model names
I decided to rename models and give them more appropriate names. Former `ClosureTable` model is now `Entity` because the database table, it operates, contains the entity data only. `TreePath` is now `ClosureTable`, named after the pattern, as it contains relationships of entities to each other.

## Columns names restriction removed
ClosureTable 1 had hardcoded columns names of the closure table: `ancestor`, `descendant`, `level`. In addition to this, `ClosureTable` model used Adjacency List feature with direct parent identifier in entity table (which column name you had to set manually if needed) and hardcoded `position` column.

Now all that stuff is obsolete. `ClosureTable` model has three constants for you to define column names you want:
1. `ClosureTable::ANCESTOR` is the ancestor column name, `ancestor` by default
2. `ClosureTable::DESCENDANT` is the descendant column name, `descendant` by default
3. `ClosureTable::DEPTH` is the depth column name, `depth` by default

All you need to do is to extend the base ClouseTable model and define column names you want in your new model.

`Entity` model doesn't use the feature from Adjacency List anymore (however a trick with direct parent presents) and has constant `Entity::POSITION` for you to define position column name you want.
