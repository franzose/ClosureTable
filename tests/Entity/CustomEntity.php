<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\ClosureTable;
use Franzose\ClosureTable\Entity;

class CustomEntity extends Entity
{
    protected $table = 'custom';

    /**
     * @return ClosureTable|null
     */
    public function getClosureTable()
    {
        return $this->closure;
    }
}
