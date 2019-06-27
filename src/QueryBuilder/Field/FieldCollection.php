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
        $this->elements[$field->getName()] = $field;

        return $this;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getByName(string $name): ?Field
    {
        return $this->elements[$name] ?? null;
    }
}
