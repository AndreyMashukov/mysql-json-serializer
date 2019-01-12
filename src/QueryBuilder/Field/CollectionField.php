<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\FieldManage;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\TableManage;

abstract class CollectionField extends Field
{
    use FieldManage;

    use TableManage;

    protected $joinField;

    public function __construct(Table $table, string $name, string $joinField)
    {
        parent::__construct($table, $name);

        $this->parameters = [];
        $this->fieldList  = new FieldCollection();
        $this->joinField  = $joinField;
    }

    /**
     * @return string
     */
    public function getJoinField(): string
    {
        return $this->joinField;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
