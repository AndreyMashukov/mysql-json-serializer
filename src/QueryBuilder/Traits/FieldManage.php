<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Traits;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

trait FieldManage
{
    /** @var FieldCollection */
    protected $fieldList;

    /** @var Table */
    protected $table;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string $name
     *
     * @return FieldManage
     */
    public function addSimpleField(string $name): self
    {
        $this->fieldList->add(Field::create($this, $name, Field::TYPE_SIMPLE));

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table         $table
     * @param string        $name
     * @param FieldStrategy $joinStrategy
     *
     * @return $this
     */
    public function addOneToManyField(Table $table, string $name, FieldStrategy $joinStrategy): self
    {
        $field = Field::create($table, $name, Field::TYPE_ONE_TO_MANY, $this, $joinStrategy);
        $this->fieldList->add($field);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table         $table
     * @param string        $name
     * @param FieldStrategy $joinStrategy
     *
     * @return $this
     */
    public function addManyToOneField(Table $table, string $name, FieldStrategy $joinStrategy): self
    {
        $field = Field::create($table, $name, Field::TYPE_MANY_TO_ONE, $this, $joinStrategy);
        $this->fieldList->add($field);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table             $table
     * @param string            $name
     * @param ReferenceStrategy $joinStrategy
     *
     * @return $this
     */
    public function addManyToManyField(Table $table, string $name, ReferenceStrategy $joinStrategy): self
    {
        $field = Field::create($table, $name, Field::TYPE_MANY_TO_MANY, null, $joinStrategy);
        $this->fieldList->add($field);

        return $this;
    }

    /**
     * @return FieldCollection
     */
    public function getFieldList(): FieldCollection
    {
        return $this->fieldList;
    }

    public function clearFields(): self
    {
        $this->fieldList->clear();

        return $this;
    }
}
