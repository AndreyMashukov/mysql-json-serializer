<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

class Table
{
    private $name;

    private $alias;

    private $idField;

    public function __construct(string $name, string $alias, string $idField)
    {
        $this->name    = $name;
        $this->alias   = $alias;
        $this->idField = $idField;
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
}
