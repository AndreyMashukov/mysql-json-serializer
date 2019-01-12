<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\FieldManage;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\TableManage;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;

class QueryBuilder
{
    use FieldManage;

    use TableManage;

    public const SELECT_OPERATOR  = 'SELECT';

    private $operator;

    private $wrapper;

    /** @var null|int */
    private $offset;

    /** @var null|int */
    private $limit;

    private $parameters = [];

    public function __construct(Table $table, FieldWrapper $fieldWrapper)
    {
        $this->fieldList = new FieldCollection();
        $this->wrapper   = $fieldWrapper;
        $this->table     = $table;
    }

    public function select(): self
    {
        $this->operator = self::SELECT_OPERATOR;

        return $this;
    }

    public function clearFields(): self
    {
        $this->fieldList->clear();

        return $this;
    }

    public function getSql(): string
    {
        if (!$this->operator) {
            throw new \RuntimeException('You should set operator, use methods: select()'); // today we have only select
        }

        $sql = $this->operator
            . ' '
            . $this->wrapper->wrap($this->fieldList)
            . ' '
            . 'FROM ' . $this->table->getName()
            . ' '
            . $this->table->getAlias()
            . $this->getJoins()
            . $this->getLimit()
            . $this->getOffset()
        ;

        return $sql;
    }

    /**
     * @param int $offset
     *
     * @return QueryBuilder
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return QueryBuilder
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setParameter(string $name, string $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    private function getJoins(): string
    {
        $joins = $this->table->getJoins()->getElements();

        if (0 === \count($joins)) {
            return '';
        }

        $part = ' ';

        foreach ($joins as $join) {
            $part .= $join;
        }

        return $part;
    }

    private function getOffset(): string
    {
        if (!$this->offset) {
            return '';
        }

        return " OFFSET {$this->offset}";
    }

    private function getLimit(): string
    {
        if (!$this->limit) {
            return '';
        }

        return " LIMIT {$this->limit}";
    }
}
