<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class Pair
{
    /** @var Table */
    private $table;

    /** @var string */
    private $field;

    public function __construct(Table $table, string $field)
    {
        $this->table = $table;
        $this->field = $field;
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }
}
