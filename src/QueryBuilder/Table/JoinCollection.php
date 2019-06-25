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

    /**
     * @param Join $join
     *
     * @return JoinCollection
     */
    public function add(Join $join): self
    {
        $this->elements[] = $join;

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }
}
