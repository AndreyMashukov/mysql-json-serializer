<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

class Table
{
    private $name;

    private $alias;

    private $idField;

    private $joins;

    public function __construct(string $name, string $alias, string $idField)
    {
        $this->name    = $name;
        $this->alias   = $alias;
        $this->idField = $idField;
        $this->joins   = new JoinCollection();
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

    public function getJoins(): JoinCollection
    {
        return $this->joins;
    }
}
