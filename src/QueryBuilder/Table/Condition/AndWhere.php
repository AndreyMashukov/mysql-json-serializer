<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition;

class AndWhere extends Where
{
    public function __toString(): string
    {
        return $this->condition;
    }
}
