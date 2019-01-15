<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder;

use Mash\MysqlJsonSerializer\Annotation\Expose;
use Mash\MysqlJsonSerializer\QueryBuilder\SQL\JsonArray;
use Mash\MysqlJsonSerializer\QueryBuilder\SQL\JsonPagination;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\PartHelper;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\TableManage;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;

class QueryBuilder
{
    use TableManage;

    use PartHelper;

    public const SELECT_OPERATOR  = 'SELECT';

    private $operator = self::SELECT_OPERATOR;

    private $wrapper;

    /** @var null|int */
    private $offset;

    /** @var null|int */
    private $limit;

    private $orderBy = [];

    private $groupBy;

    private $groups = Expose::DEFAULT_GROUPS;

    public function __construct(Table $table, FieldWrapper $fieldWrapper)
    {
        $this->wrapper    = $fieldWrapper;
        $this->table      = $table;
        $this->parameters = [];
    }

    public function jsonArray(): JsonArray
    {
        return new JsonArray($this->getParameters(), $this->getSql());
    }

    public function jsonPagination(int $page, int $limit)
    {
        $this->setLimit($limit);
        $this->setOffset(($page - 1) * $limit);

        return new JsonPagination($this->getParameters(), $this->getSql(), $this->getCountSql(), $limit, $page);
    }

    private function getSql(): string
    {
        if (!$this->operator) {
            throw new \RuntimeException('You should set operator, use methods: select()'); // today we have only select
        }

        $sql = $this->operator
            . ' '
            . "JSON_ARRAYAGG({$this->wrapper->select($this->table, $this->groups, '_res')})"
            . ' '
            . "FROM (SELECT * FROM {$this->table->getName()} {$this->table->getAlias()}"
            . ' '
            . $this->getMainSql()
            . $this->getLimit()
            . $this->getOffset()
            . ') '
            . "{$this->table->getAlias()}_res"
        ;

        return $sql;
    }

    private function getMainSql(): string
    {
        $where = $this->getWhere($this->table);
        $sql   = $this->getJoins($this->table)
            . ('' === $where ? '' : ' WHERE ' . $where)
            . $this->getGroupBy()
            . $this->getOrderBy()
        ;

        return $sql;
    }

    private function getCountSql(): string
    {
        return "SELECT COUNT({$this->table->getAlias()}_paginate.{$this->table->getIdField()}) FROM {$this->table->getName()} {$this->table->getAlias()}_paginate" . $this->getMainSql();
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

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function orderBy(string $field, string $type): self
    {
        $this->orderBy = [$field, $type];

        return $this;
    }

    public function groupBy(string $field): self
    {
        $this->groupBy = $field;

        return $this;
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

    private function getOrderBy(): string
    {
        if ([] === $this->orderBy) {
            return '';
        }

        return ' ORDER BY ' . $this->orderBy[0] . ' ' . $this->orderBy[1];
    }

    private function getGroupBy(): string
    {
        if (!$this->groupBy) {
            return '';
        }

        return ' GROUP BY ' . $this->groupBy;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }
}
