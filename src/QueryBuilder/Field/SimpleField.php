<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\Wrapper\Type\CustomTypeInterface;

class SimpleField extends Field
{
    /** @var null|CustomTypeInterface */
    private $type;

    public function setType(CustomTypeInterface $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|CustomTypeInterface
     */
    public function getType(): ?CustomTypeInterface
    {
        return $this->type;
    }
}
