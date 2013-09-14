<?php

use Franzose\ClosureTable\Entity;

class Page extends Entity {
    protected $closure = 'pages_closure';
    protected $fillable = array('position', 'title', 'excerpt', 'content');
}