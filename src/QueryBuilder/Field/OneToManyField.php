<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\FieldManage;

class OneToManyField extends Field implements RelationInterface
{
    use FieldManage;

    private $parent;

    private $joinField;

    public function __construct(Table $table, string $name, Table $parent, string $joinField)
    {
        parent::__construct($table, $name);

        $this->parent    = $parent;
        $this->joinField = $joinField;
        $this->fieldList = new FieldCollection();
    }

    public function getParent(): Table
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getJoinField(): string
    {
        return $this->joinField;
    }
}
