<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Traits;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\AndWhere;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\OrWhere;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Join;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

trait TableManage
{
    /** @var Table */
    protected $table;

    protected $parameters;

    public function andWhere(string $condition): self
    {
        $this->table->addWhere(new AndWhere($condition));

        return $this;
    }

    public function orWhere(string $condition): self
    {
        $this->table->addWhere(new OrWhere($condition));

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table  $joinTable
     * @param string $condition
     *
     * @return $this
     */
    public function innerJoin(Table $joinTable, string $condition): self
    {
        $this->table->addJoin(Join::create($joinTable, Join::TYPE_INNER, $condition));

        return $this;
    }

    public function setParameter(string $name, string $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }
}
