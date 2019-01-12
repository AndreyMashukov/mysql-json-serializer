<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition;

class OrWhere extends Where
{
    public function __toString(): string
    {
        return $this->condition;
    }
}
