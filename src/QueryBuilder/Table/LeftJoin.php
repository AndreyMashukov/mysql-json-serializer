<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

class LeftJoin extends Join
{
    protected function getType(): string
    {
        return 'LEFT JOIN';
    }
}
