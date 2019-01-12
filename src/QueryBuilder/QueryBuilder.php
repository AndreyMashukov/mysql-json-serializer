<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\CollectionField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\FieldManage;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\PartHelper;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\TableManage;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;

class QueryBuilder
{
    use FieldManage;

    use TableManage;

    use PartHelper;

    public const SELECT_OPERATOR  = 'SELECT';

    private $operator;

    private $wrapper;

    /** @var null|int */
    private $offset;

    /** @var null|int */
    private $limit;

    private $orderBy = [];

    private $groupBy;

    public function __construct(Table $table, FieldWrapper $fieldWrapper)
    {
        $this->fieldList  = new FieldCollection();
        $this->wrapper    = $fieldWrapper;
        $this->table      = $table;
        $this->parameters = [];
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

        $where = $this->getWhere($this->table);
        $sql   = $this->operator
            . ' '
            . $this->wrapper->wrap($this->fieldList)
            . ' '
            . 'FROM ' . $this->table->getName()
            . ' '
            . $this->table->getAlias()
            . $this->getJoins($this->table)
            . ('' === $where ? '' : ' WHERE ' . $where)
            . $this->getGroupBy()
            . $this->getOrderBy()
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

    public function getParameters(): array
    {
        return \array_merge($this->parameters, $this->joinFields($this->fieldList->getElements()));
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

    private function joinFields(array $fields): array
    {
        $parameters = [];

        /** @var CollectionField $element */
        foreach ($fields as $element) {
            if (!$element instanceof CollectionField) {
                continue;
            }

            $elements = $element->getFieldList()->getElements();

            if (0 === \count($elements)) {
                $parameters = \array_merge($parameters, $element->getParameters());

                continue;
            }

            $parameters = \array_merge($element->getParameters(), $this->joinFields($elements));
        }

        return $parameters;
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
}
