<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class Mapping
{
    private $map = [];

    public function addMap(Table $table, string $name, string $alias): self
    {
        $tableAlias = $table->getAlias();

        if (!isset($this->map[$tableAlias])) {
            $this->map[$tableAlias] = [];
        }

        $this->map[$tableAlias][$name] = $alias;

        return $this;
    }

    public function getAlias(Field $field): string
    {
        $name = $field->getName();

        return $this->map[$field->getTable()->getAlias()][$name] ?? $name;
    }
}
