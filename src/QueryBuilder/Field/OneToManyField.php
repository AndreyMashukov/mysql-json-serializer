<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class OneToManyField extends CollectionField implements RelationInterface
{
    private $parent;

    public function __construct(Table $table, string $name, Table $parent, FieldStrategy $strategy)
    {
        parent::__construct($table, $name, $strategy);

        $this->parent = $parent;
    }

    public function getParent(): Table
    {
        return $this->parent;
    }
}
