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

    public function wrap(FieldCollection $collection): string
    {
        $data = '';

        /** @var Field $item */
        foreach ($collection->getElements() as $item) {
            if ($item instanceof SimpleField) {
                $data .= $item->getTableAlias() . '.' . $item->getName() . ',' . $this->mapping->getAlias($item);

                continue;
            }
        }

        return "JSON_OBJECT({$data})";
    }
}
