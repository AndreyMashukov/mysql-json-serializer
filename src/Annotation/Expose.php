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
}
