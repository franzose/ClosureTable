<?php
namespace Franzose\ClosureTable\Tests\Models;

use Franzose\ClosureTable\Models\Entity;

class Page extends Entity
{
    protected $table = 'entities';
    protected $fillable = ['id', 'title', 'excerpt', 'body', 'position', 'real_depth'];
}
