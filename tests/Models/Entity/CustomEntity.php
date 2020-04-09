<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;

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
