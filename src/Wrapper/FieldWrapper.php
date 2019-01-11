<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
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
            $data .= $field->getTableAlias() . '.' . $field->getName() . ",'" . $this->mapping->getAlias($field) . "'";

            return;
        }

        // other types...
    }
}
