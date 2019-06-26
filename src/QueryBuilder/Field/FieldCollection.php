<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

/**
 * Class FieldCollection.
 */
class FieldCollection
{
    private $elements = [];

    public function clear(): self
    {
        $this->elements = [];

        return $this;
    }

    public function add(Field $field): self
    {
        $this->elements[] = $field;

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }
}
