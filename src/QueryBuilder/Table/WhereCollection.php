<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\Where;

class WhereCollection
{
    private $elements = [];

    public function clear(): self
    {
        $this->elements = [];

        return $this;
    }

    /**
     * @param Where $where
     *
     * @return WhereCollection
     */
    public function add(Where $where): self
    {
        $this->elements[] = $where;

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }
}
