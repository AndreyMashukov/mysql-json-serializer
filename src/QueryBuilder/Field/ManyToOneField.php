<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class ManyToOneField extends CollectionField implements RelationInterface
{
    private $child;

    public function __construct(Table $table, string $name, Table $child, FieldStrategy $joinField)
    {
        parent::__construct($table, $name, $joinField);

        $this->child = $child;
    }

    /**
     * @return Table
     */
    public function getChild(): Table
    {
        return $this->child;
    }
}
