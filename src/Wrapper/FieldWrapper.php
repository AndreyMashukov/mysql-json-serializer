<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToOneField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\RelationInterface;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\SimpleField;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\PartHelper;

class FieldWrapper
{
    use PartHelper;

    private $mapping;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @param FieldCollection $collection
     *
     * @return string
     */
    public function wrap(FieldCollection $collection): string
    {
        $data   = '';
        $prefix = '';

        /** @var Field $item */
        foreach ($collection->getElements() as $item) {
            $data .= $prefix;

            $this->wrapField($data, $item);
            $prefix = ','; // add after first
        }

        return "JSON_OBJECT({$data})";
    }

    /**
     * @param string                              $data
     * @param Field|ManyToOneField|OneToManyField $field
     */
    private function wrapField(string &$data, Field $field)
    {
        if ($field instanceof SimpleField) {
            $data .= "'" . $this->mapping->getAlias($field) . "'," . $field->getTable()->getAlias() . '.' . $field->getName();

            return;
        }

        $data .= "'" . $this->mapping->getAlias($field) . "'," . $this->subSelect($field);
    }

    /**
     * @param ManyToOneField|OneToManyField|RelationInterface $field
     *
     * @return string
     */
    private function subSelect(RelationInterface $field): string
    {
        if ($field instanceof OneToManyField) {
            return $this->getOneToMany($field);
        }

        if ($field instanceof ManyToOneField) {
            return $this->getManyToOne($field);
        }

        return $this->getManyToMany($field);
    }

    private function getOneToMany(OneToManyField $field): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        //$where = $this->getWhere($table);
        $sql   = "(SELECT (CASE WHEN {$table->getAlias()}.{$field->getStrategy()} IS NULL THEN NULL ELSE CAST(CONCAT('[', GROUP_CONCAT({$this->wrap($field->getFieldList())}), ']') AS JSON) END) "
            . "FROM {$table->getName()} {$table->getAlias()} "
            . "INNER JOIN {$parent->getName()} {$parent->getAlias()}_2 ON {$parent->getAlias()}_2.{$parent->getIdField()} = {$table->getAlias()}.{$field->getStrategy()} "
            . "WHERE {$parent->getAlias()}_2.{$parent->getIdField()} = {$parent->getAlias()}.{$parent->getIdField()} "
            . "GROUP BY {$parent->getAlias()}_2.{$parent->getIdField()})"
        ;

        return $sql;
    }

    private function getManyToOne(ManyToOneField $field): string
    {
        $child = $field->getChild();
        $table = $field->getTable();

        $where = $this->getWhere($table);
        $sql   = '('
            . "SELECT {$this->wrap($field->getFieldList())} "
            . "FROM {$table->getName()} {$table->getAlias()} "
            . $this->getJoins($table)
            . "WHERE {$table->getAlias()}.{$table->getIdField()} = {$child->getAlias()}.{$field->getStrategy()}"
            . ('' === $where ? '' : " AND ({$where})")
            . ' '
            . 'LIMIT 1)'
        ;

        return $sql;
    }

    private function getManyToMany(ManyToManyField $field): string
    {
        $table = $field->getTable();
        /** @var ReferenceStrategy $strategy */
        $strategy       = $field->getStrategy()->getStrategy();
        $main           = $strategy->getFirst()->getFirst();
        $mainRef        = $strategy->getFirst()->getSecond();
        $collection     = $strategy->getSecond()->getFirst();
        $collectionXref = $strategy->getSecond()->getSecond();

        $where = $this->getWhere($table);
        $sql   = "(SELECT (CASE WHEN {$collectionXref->getTable()->getAlias()}.{$collectionXref->getField()} IS NULL THEN NULL ELSE CAST(CONCAT('[', GROUP_CONCAT({$this->wrap($field->getFieldList())}), ']') AS JSON) END) "
            . "FROM {$table->getName()} {$table->getAlias()} "
            . $this->getJoins($table) . ' '
            . "INNER JOIN {$collectionXref->getTable()->getName()} {$collectionXref->getTable()->getAlias()} "
            . "ON {$collection->getTable()->getAlias()}.{$collection->getField()} = "
            . "{$collectionXref->getTable()->getAlias()}.{$collectionXref->getField()} "
            . "WHERE {$main->getTable()->getAlias()}.{$main->getField()} = "
            . "{$mainRef->getTable()->getAlias()}.{$mainRef->getField()})"
            . ('' === $where ? '' : " AND ({$where})")
        ;

        return $sql;
    }
}
