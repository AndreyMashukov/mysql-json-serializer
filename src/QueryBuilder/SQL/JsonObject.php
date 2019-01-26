<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\SQL;

class JsonObject extends SQL
{
    private $sql;

    public function __construct(array $parameters, string $sql)
    {
        parent::__construct($parameters);

        $this->sql = $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }
}
