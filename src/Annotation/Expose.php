<?php

namespace Mash\MysqlJsonSerializer\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 *
 * @Target({"PROPERTY"})
 */
class Expose extends ConfigurationAnnotation
{
    public const DEFAULT_GROUPS = [
        self::DEFAULT_GROUP,
    ];

    public const DEFAULT_GROUP = 'Default';

    /** @var array */
    private $groups = self::DEFAULT_GROUPS;

    /** @var null|string */
    private $type;

    public function __construct(array $values)
    {
        parent::__construct($values);
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
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     *
     * @return Expose
     */
    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Expose
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
