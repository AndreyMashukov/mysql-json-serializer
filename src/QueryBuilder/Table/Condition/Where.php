<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition;

abstract class Where
{
    protected $condition;

    public function __construct(string $condition)
    {
        $this->condition = $condition;
    }

    abstract public function __toString(): string;
}
