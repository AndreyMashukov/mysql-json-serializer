<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy;

class FieldStrategy implements JoinStrategyInterface
{
    private $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function getStrategy()
    {
        return $this->field;
    }

    public function __toString(): string
    {
        return $this->getStrategy();
    }
}
