<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Traits;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

trait FieldManage
{
    /** @var FieldCollection */
    protected $fieldList;

    /** @var Table */
    protected $table;

    public function addSimpleField(string $name): self
    {
        $this->fieldList->add(Field::create($this->table, $name, Field::TYPE_SIMPLE));

        return $this;
    }

    public function addOneToManyField(Table $table, string $name, string $joinField): OneToManyField
    {
        $field = Field::create($table, $name, Field::TYPE_ONE_TO_MANY, $this->table, $joinField);
        $this->fieldList->add($field);

        return $field;
    }
}
