<?php
declare(strict_types=1);

namespace Franzose\ClosureTable;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Basic ClosureTable model. Performs actions on the relationships table.
 *
 * @property mixed ancestor Alias for the ancestor attribute name
 * @property mixed descendant Alias for the descendant attribute name
 * @property int depth Alias for the depth attribute name
 */
class ClosureTable extends Eloquent
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'entities_closure';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'closure_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Inserts new node into closure table.
     */
    public function insertNode(mixed $ancestorId, mixed $descendantId): void
    {
        $rows = $this->selectRowsToInsert($ancestorId, $descendantId);

        if (count($rows) > 0) {
            $this->insert($rows);
        }
    }

    private function selectRowsToInsert(mixed $ancestorId, mixed $descendantId): array
    {
        $table = $this->getPrefixedTable();
        $ancestor = $this->getAncestorColumn();
        $descendant = $this->getDescendantColumn();
        $depth = $this->getDepthColumn();

        $select = "
            SELECT tbl.{$ancestor} AS {$ancestor}, ? AS {$descendant}, tbl.{$depth}+1 AS {$depth}
            FROM {$table} AS tbl
            WHERE tbl.{$descendant} = ?
            UNION ALL
            SELECT ? AS {$ancestor}, ? AS {$descendant}, 0 AS {$depth}
        ";

        $rows = $this->getConnection()->select($select, [
            $descendantId,
            $ancestorId,
            $descendantId,
            $descendantId
        ]);

        return array_map(static function ($row) {
            return (array) $row;
        }, $rows);
    }

    /**
     * Make a node a descendant of another ancestor or makes it a root node.
     */
    public function moveNodeTo(mixed $ancestorId = null): void
    {
        $table = $this->getPrefixedTable();
        $ancestor = $this->getAncestorColumn();
        $descendant = $this->getDescendantColumn();
        $depth = $this->getDepthColumn();

        // Prevent constraint collision
        if ($ancestorId !== null && $this->ancestor === $ancestorId) {
            return;
        }

        $this->unbindRelationships();

        // Since we have already unbound the node relationships,
        // given null ancestor id, we have nothing else to do,
        // because now the node is already root.
        if ($ancestorId === null) {
            return;
        }

        $query = "
            INSERT INTO {$table} ({$ancestor}, {$descendant}, {$depth})
            SELECT supertbl.{$ancestor}, subtbl.{$descendant}, supertbl.{$depth}+subtbl.{$depth}+1
            FROM {$table} as supertbl
            CROSS JOIN {$table} as subtbl
            WHERE supertbl.{$descendant} = ?
            AND subtbl.{$ancestor} = ?
        ";

        $this->getConnection()->statement($query, [
            $ancestorId,
            $this->descendant
        ]);
    }

    /**
     * Unbinds current relationships.
     */
    protected function unbindRelationships(): void
    {
        $table = $this->getPrefixedTable();
        $ancestorColumn = $this->getAncestorColumn();
        $descendantColumn = $this->getDescendantColumn();

        $query = "
            DELETE FROM {$table}
            WHERE {$descendantColumn} IN (
              SELECT d FROM (
                SELECT {$descendantColumn} AS d FROM {$table}
                WHERE {$ancestorColumn} = ?
              ) AS dct
            )
            AND {$ancestorColumn} IN (
              SELECT a FROM (
                SELECT {$ancestorColumn} AS a FROM {$table}
                WHERE {$descendantColumn} = ?
                AND {$ancestorColumn} <> ?
              ) AS ct
            )
        ";

        $this->getConnection()->delete($query, [
            $this->descendant,
            $this->descendant,
            $this->descendant
        ]);
    }

    /**
     * Get table name with custom prefix for use in raw queries.
     */
    public function getPrefixedTable(): string
    {
        return $this->getConnection()->getTablePrefix() . $this->getTable();
    }

    /**
     * Get value of the "ancestor" attribute.
     */
    public function getAncestorAttribute(): mixed
    {
        return $this->getAttributeFromArray($this->getAncestorColumn());
    }

    /**
     * Set new ancestor id.
     */
    public function setAncestorAttribute(mixed $value): void
    {
        $this->attributes[$this->getAncestorColumn()] = $value;
    }

    /**
     * Get the fully qualified "ancestor" column.
     */
    public function getQualifiedAncestorColumn(): string
    {
        return $this->getTable() . '.' . $this->getAncestorColumn();
    }

    /**
     * Get the short name of the "ancestor" column.
     */
    public function getAncestorColumn(): string
    {
        return 'ancestor';
    }

    /**
     * Get value of the "descendant" attribute.
     */
    public function getDescendantAttribute(): mixed
    {
        return $this->getAttributeFromArray($this->getDescendantColumn());
    }

    /**
     * Set new descendant id.
     */
    public function setDescendantAttribute(mixed $value): void
    {
        $this->attributes[$this->getDescendantColumn()] = $value;
    }

    /**
     * Get the fully qualified "descendant" column.
     */
    public function getQualifiedDescendantColumn(): string
    {
        return $this->getTable() . '.' . $this->getDescendantColumn();
    }

    /**
     * Get the short name of the "descendant" column.
     */
    public function getDescendantColumn(): string
    {
        return 'descendant';
    }

    /**
     * Gets value of the "depth" attribute.
     */
    public function getDepthAttribute(): int
    {
        return $this->getAttributeFromArray($this->getDepthColumn());
    }

    /**
     * Sets new depth.
     */
    public function setDepthAttribute(int $value): void
    {
        $this->attributes[$this->getDepthColumn()] = $value;
    }

    /**
     * Gets the fully qualified "deleted at" column.
     */
    public function getQualifiedDepthColumn(): string
    {
        return $this->getTable() . '.' . $this->getDepthColumn();
    }

    /**
     * Get the short name of the "depth" column.
     */
    public function getDepthColumn(): string
    {
        return 'depth';
    }
}
