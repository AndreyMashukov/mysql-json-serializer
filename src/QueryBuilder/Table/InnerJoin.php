<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

class InnerJoin extends Join
{
    protected function getType(): string
    {
        return 'INNER JOIN';
    }
}
