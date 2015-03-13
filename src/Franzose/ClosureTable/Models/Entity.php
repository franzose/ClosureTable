<?php
namespace Franzose\ClosureTable\Models;

use Franzose\ClosureTable\Contracts\EntityInterface;
use Franzose\ClosureTable\Traits\EntityTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Basic entity class.
 */
class Entity extends Eloquent implements EntityInterface
{
    use EntityTrait;

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public static $debug = false;

    /**
     * ClosureTable model instance.
     *
     * @var ClosureTable
     */
    protected $closure = 'Franzose\ClosureTable\Models\ClosureTable';

    public function __construct(array $attributes = [])
    {
        $attributes = $this->initialiseEntityTrait($attributes);

        parent::__construct($attributes);
    }
}
