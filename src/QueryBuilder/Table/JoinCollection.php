<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

class JoinCollection
{
    private $elements = [];

    public function clear(): self
    {
        $this->elements = [];

        return $this;
    }

    public function add(Join $field): self
    {
        $this->elements[] = $field;

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }
}
