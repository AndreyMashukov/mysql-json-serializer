<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;

class Mapping
{
    private $map = [];

    public function addMap(string $name, string $alias): self
    {
        $this->map[$name] = $alias;

        return $this;
    }

    public function getAlias(Field $field): string
    {
        $name = $field->getName();

        return $this->map[$name] ?? $name;
    }
}
