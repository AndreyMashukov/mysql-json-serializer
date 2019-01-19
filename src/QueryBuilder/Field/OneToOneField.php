<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class OneToOneField extends CollectionField implements RelationInterface
{
    private $parent;

    public function __construct(Table $table, string $name, Table $parent, FieldStrategy $joinField)
    {
        parent::__construct($table, $name, $joinField);

        $this->parent = $parent;
    }

    /**
     * @return Table
     */
    public function getParent(): Table
    {
        return $this->parent;
    }
}
