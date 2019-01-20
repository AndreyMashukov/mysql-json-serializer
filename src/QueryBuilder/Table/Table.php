<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

use Mash\MysqlJsonSerializer\Annotation\Expose;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\Where;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;

class Table
{
    /** @var FieldCollection */
    protected $fieldList;

    private $name;

    private $alias;

    private $idField;

    private $joins;

    /** @var WhereCollection */
    protected $where;

    public function __construct(string $name, string $alias, string $idField = 'id')
    {
        $this->name      = $name;
        $this->alias     = $alias;
        $this->idField   = $idField;
        $this->joins     = new JoinCollection();
        $this->where     = new WhereCollection();
        $this->fieldList = new FieldCollection();
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

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string $name
     * @param array  $serializeGroups
     *
     * @return Table
     */
    public function addSimpleField(string $name, array $serializeGroups = Expose::DEFAULT_GROUPS): self
    {
        $this->fieldList->add(
            Field::create(
                $this,
                $name,
                Field::TYPE_SIMPLE,
                null,
                null,
                $serializeGroups
            )
        );

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table         $table
     * @param string        $name
     * @param FieldStrategy $joinStrategy
     * @param array         $serializeGroups
     *
     * @return Table
     */
    public function addOneToManyField(self $table, string $name, FieldStrategy $joinStrategy, array $serializeGroups = Expose::DEFAULT_GROUPS): self
    {
        $field = Field::create(
            $table,
            $name,
            Field::TYPE_ONE_TO_MANY,
            $this,
            $joinStrategy,
            $serializeGroups
        );

        $this->fieldList->add($field);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table         $table
     * @param string        $name
     * @param FieldStrategy $joinStrategy
     * @param array         $serializeGroups
     *
     * @return Table
     */
    public function addManyToOneField(self $table, string $name, FieldStrategy $joinStrategy, array $serializeGroups = Expose::DEFAULT_GROUPS): self
    {
        $field = Field::create(
            $table,
            $name,
            Field::TYPE_MANY_TO_ONE,
            $this,
            $joinStrategy,
            $serializeGroups
        );

        $this->fieldList->add($field);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table         $table
     * @param string        $name
     * @param FieldStrategy $joinStrategy
     * @param array         $serializeGroups
     *
     * @return Table
     */
    public function addOneToOneField(self $table, string $name, FieldStrategy $joinStrategy, array $serializeGroups = Expose::DEFAULT_GROUPS): self
    {
        $field = Field::create(
            $table,
            $name,
            Field::TYPE_ONE_TO_ONE,
            $this,
            $joinStrategy,
            $serializeGroups
        );

        $this->fieldList->add($field);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param Table             $table
     * @param string            $name
     * @param ReferenceStrategy $joinStrategy
     * @param array             $serializeGroups
     *
     * @return $this
     */
    public function addManyToManyField(self $table, string $name, ReferenceStrategy $joinStrategy, array $serializeGroups = Expose::DEFAULT_GROUPS): self
    {
        $field = Field::create(
            $table,
            $name,
            Field::TYPE_MANY_TO_MANY,
            null,
            $joinStrategy,
            $serializeGroups
        );

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
