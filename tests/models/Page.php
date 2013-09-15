<?php

use Franzose\ClosureTable\Entity;

class Page extends Entity {
    protected $closure = 'pages_closure';
    protected $fillable = array('title', 'excerpt', 'content');
}