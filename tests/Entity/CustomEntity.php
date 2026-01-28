<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\ClosureTable;
use Franzose\ClosureTable\Entity;

class CustomEntity extends Entity
{
    protected $table = 'custom';

    public function getClosureTable(): ClosureTable
    {
        return $this->closure;
    }
}
