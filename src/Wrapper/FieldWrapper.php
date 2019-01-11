<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\SimpleField;

class FieldWrapper
{
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
     * @param string $data
     * @param Field  $field
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

        // other types...
    }

    private function subSelect(OneToManyField $field): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        return "JSON_ARRAY((SELECT GROUP_CONCAT({$this->wrap($field->getFieldList())}) "
            . "FROM {$table->getName()} {$table->getAlias()} "
            . "WHERE {$table->getAlias()}.{$field->getJoinField()} = {$parent->getAlias()}.{$parent->getIdField()}))";
    }
}
