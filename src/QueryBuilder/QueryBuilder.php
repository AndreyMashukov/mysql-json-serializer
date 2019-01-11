<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;

class QueryBuilder
{
    public const SELECT_OPERATOR  = 'SELECT';

    private $table;

    private $operator;

    private $alias;

    private $fieldList;

    private $wrapper;

    public function __construct(string $table, string $alias, FieldWrapper $fieldWrapper)
    {
        $this->fieldList = new FieldCollection();
        $this->wrapper   = $fieldWrapper;
        $this->table     = $table;
        $this->alias     = $alias;
    }

    public function select(): self
    {
        $this->operator = self::SELECT_OPERATOR;

        return $this;
    }

    public function addSimpleField(string $name): self
    {
        $this->fieldList->add(Field::create($this->alias, $name, Field::TYPE_SIMPLE));

        return $this;
    }

    public function clearFields(): self
    {
        $this->fieldList->clear();

        return $this;
    }

    public function getSql(): string
    {
        return $this->operator . ' ' . $this->wrapper->wrap($this->fieldList) . ' FROM ' . $this->table . ' ' . $this->alias;
    }
}
