<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Traits;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\AndWhere;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\OrWhere;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Join;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\Service\TableManager;

trait TableManage
{
    /** @var Table */
    protected $table;

    protected $parameters;

    /** @var TableManager */
    protected $tableManager;

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
     * @param string|Table $joinTable
     * @param string       $condition
     *
     * @return $this
     */
    public function innerJoin($joinTable, string $condition): self
    {
        $joinTable = $this->getTableObject($joinTable);

        $this->table->addJoin(Join::create($joinTable, Join::TYPE_INNER, $condition));

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string|Table $joinTable
     * @param string       $condition
     *
     * @return $this
     */
    public function leftJoin($joinTable, string $condition): self
    {
        $joinTable = $this->getTableObject($joinTable);

        $this->table->addJoin(Join::create($joinTable, Join::TYPE_LEFT, $condition));

        return $this;
    }

    public function setParameter(string $name, string $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    private function getTableObject($joinTable): Table
    {
        if ($joinTable instanceof Table) {
            return $joinTable;
        }

        if (!\is_string($joinTable)) {
            throw new \InvalidArgumentException('JoinTable should be Class name string or Table object.');
        }

        if (!\class_exists($joinTable)) {
            throw new \InvalidArgumentException('Class is not exists.');
        }

        return $this->tableManager->getTable($joinTable);
    }
}
