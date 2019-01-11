<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\FieldManage;

class ManyToOneField extends Field implements RelationInterface
{
    use FieldManage;

    private $child;

    private $joinField;

    public function __construct(Table $table, string $name, Table $child, string $joinField)
    {
        parent::__construct($table, $name);

        $this->child     = $child;
        $this->joinField = $joinField;
        $this->fieldList = new FieldCollection();
    }

    /**
     * @return Table
     */
    public function getChild(): Table
    {
        return $this->child;
    }

    /**
     * @return string
     */
    public function getJoinField(): string
    {
        return $this->joinField;
    }
}
