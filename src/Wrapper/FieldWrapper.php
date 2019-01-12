<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToOneField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\RelationInterface;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\SimpleField;
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

        if ($field instanceof OneToManyField) {
            $data .= "'" . $this->mapping->getAlias($field) . "'," . $this->subSelect($field);

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

        return $this->getManyToOne($field);
    }

    private function getOneToMany(OneToManyField $field): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        return "JSON_ARRAY((SELECT GROUP_CONCAT({$this->wrap($field->getFieldList())}) "
            . "FROM {$table->getName()} {$table->getAlias()} "
            . $this->getJoins($table)
            . "WHERE {$table->getAlias()}.{$field->getJoinField()} = {$parent->getAlias()}.{$parent->getIdField()}))"
            . $this->getWhere($table)
            ;
    }

    private function getManyToOne(ManyToOneField $field): string
    {
        $child = $field->getChild();
        $table = $field->getTable();

        $where = $this->getWhere($table);
        $sql   = '('
            . "SELECT {$this->wrap($field->getFieldList())} "
            . "FROM {$table->getName()} {$table->getAlias()} "
            . "WHERE {$table->getAlias()}.{$table->getIdField()} = {$child->getAlias()}.{$field->getJoinField()}"
            . ('' === $where ? '' : " AND ({$where})")
            . ' '
            . 'LIMIT 1)'
        ;

        return $sql;
    }
}
