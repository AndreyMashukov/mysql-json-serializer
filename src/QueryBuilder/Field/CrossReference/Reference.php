<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference;

class Reference
{
    private $first;

    private $second;

    public function __construct(Pair $first, Pair $second)
    {
        $this->first  = $first;
        $this->second = $second;
    }

    /**
     * @return Pair
     */
    public function getFirst(): Pair
    {
        return $this->first;
    }

    /**
     * @return Pair
     */
    public function getSecond(): Pair
    {
        return $this->second;
    }
}
