<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

abstract class Field
{
    protected $name;

    protected $tableAlias;

    public function __construct(string $tableAlias, string $name)
    {
        $this->name       = $name;
        $this->tableAlias = $tableAlias;
    }

    public const TYPE_SIMPLE = '@simple';

    public const TYPE_ONE_TO_MANY = '@oneToMany';

    public const TYPE_MANY_TO_ONE = '@manyToOne';

    private const ALLOWED_TYPES = [
        self::TYPE_SIMPLE      => true,
        self::TYPE_ONE_TO_MANY => true,
        self::TYPE_MANY_TO_ONE => true,
    ];

    public static function create(string $alias, string $name, string $type)
    {
        if (!isset(self::ALLOWED_TYPES[$type])) {
            throw new \InvalidArgumentException(self::class . ': Allowed types: ' . \implode(', ', \array_keys(self::ALLOWED_TYPES)));
        }

        switch ($type) {
            case self::TYPE_SIMPLE:
                return new SimpleField($alias, $name);
            case self::TYPE_MANY_TO_ONE:
                return new ManyToOneField($alias, $name);
            case self::TYPE_ONE_TO_MANY:
                return new OneToManyField($alias, $name);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }
}
