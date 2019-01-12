<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Traits;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Join;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

trait TableManage
{
    /** @var Table */
    protected $table;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table  $joinTable
     * @param string $condition
     *
     * @return $this
     */
    public function innerJoin(Table $joinTable, string $condition)
    {
        $this->table->addJoin(Join::create($joinTable, Join::TYPE_INNER, $condition));

        return $this;
    }
}
