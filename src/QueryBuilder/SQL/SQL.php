<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\SQL;

abstract class SQL implements SQLProviderInterface
{
    protected $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    abstract public function getSql(): string;

    public function __toString()
    {
        return $this->getSql();
    }
}
