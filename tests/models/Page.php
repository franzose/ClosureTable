<?php

use Franzose\ClosureTable\Entity;

class Page extends Entity {
    protected $fillable = array('title', 'excerpt', 'content','language');
}