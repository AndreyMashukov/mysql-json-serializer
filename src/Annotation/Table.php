<?php

namespace Mash\MysqlJsonSerializer\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 *
 * @Target({"CLASS"})
 */
class Table extends ConfigurationAnnotation
{
    private $alias;

    private $map = [];

    public function __construct(array $values)
    {
        parent::__construct($values);
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getAliasName()
    {
        return 'Alias';
    }

    /**
     * @return bool
     */
    public function allowArray()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @param array $map
     *
     * @return Table
     */
    public function setMap(array $map): self
    {
        $this->map = $map;

        return $this;
    }
}
