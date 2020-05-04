<?php
namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Models\Entity;

class Page extends Entity
{
    protected $table = 'entities';
    protected $fillable = ['id', 'parent_id', 'title', 'excerpt', 'body', 'position'];
}
