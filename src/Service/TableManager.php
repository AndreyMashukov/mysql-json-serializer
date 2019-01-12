<?php

namespace Mash\MysqlJsonSerializer\Service;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class TableManager
{
    private $data = [];

    public function addTable(Table $table, string $association): self
    {
        $this->data[$association] = $table;

        return $this;
    }

    public function getTable(string $association): ?Table
    {
        return $this->data[$association] ?? null;
    }
}
