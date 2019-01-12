<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\Where;

class Table
{
    private $name;

    private $alias;

    private $idField;

    private $joins;

    /** @var WhereCollection */
    protected $where;

    public function __construct(string $name, string $alias, string $idField = 'id')
    {
        $this->name    = $name;
        $this->alias   = $alias;
        $this->idField = $idField;
        $this->joins   = new JoinCollection();
        $this->where   = new WhereCollection();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getIdField(): string
    {
        return $this->idField;
    }

    public function addJoin(Join $join): self
    {
        $this->joins->add($join);

        return $this;
    }

    public function addWhere(Where $where): self
    {
        $this->where->add($where);

        return $this;
    }

    public function getJoins(): JoinCollection
    {
        return $this->joins;
    }

    public function getWhere(): WhereCollection
    {
        return $this->where;
    }
}
