<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

abstract class Field
{
    protected $name;

    protected $table;

    public function __construct(Table $table, string $name)
    {
        $this->name  = $name;
        $this->table = $table;
    }

    public const TYPE_SIMPLE = '@simple';

    public const TYPE_ONE_TO_MANY = '@oneToMany';

    public const TYPE_MANY_TO_ONE = '@manyToOne';

    private const ALLOWED_TYPES = [
        self::TYPE_SIMPLE      => true,
        self::TYPE_ONE_TO_MANY => true,
        self::TYPE_MANY_TO_ONE => true,
    ];

    public static function create(Table $table, string $name, string $type, ?Table $relatedTable = null, ?string $joinField = null)
    {
        if (!isset(self::ALLOWED_TYPES[$type])) {
            throw new \InvalidArgumentException(self::class . ': Allowed types: ' . \implode(', ', \array_keys(self::ALLOWED_TYPES)));
        }

        switch ($type) {
            case self::TYPE_SIMPLE:
                return new SimpleField($table, $name);
            case self::TYPE_MANY_TO_ONE:
                return new ManyToOneField($table, $name, $relatedTable, $joinField);
            case self::TYPE_ONE_TO_MANY:
                return new OneToManyField($table, $name, $relatedTable, $joinField);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }
}
