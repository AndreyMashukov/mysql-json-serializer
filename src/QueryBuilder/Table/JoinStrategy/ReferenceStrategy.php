<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Reference;

class ReferenceStrategy implements JoinStrategyInterface
{
    private $first;

    private $second;

    public function __construct(Reference $first, Reference $second)
    {
        $this->first  = $first;
        $this->second = $second;
    }

    public function getStrategy()
    {
        return $this;
    }

    /**
     * @return Reference
     */
    public function getFirst(): Reference
    {
        return $this->first;
    }

    /**
     * @return Reference
     */
    public function getSecond(): Reference
    {
        return $this->second;
    }
}
