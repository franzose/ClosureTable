<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests;

use Franzose\ClosureTable\Entity;

class Page extends Entity
{
    protected $table = 'entities';
    protected $fillable = ['id', 'parent_id', 'title', 'excerpt', 'body', 'position'];
}
