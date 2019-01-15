<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\Annotation\Expose;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\JoinStrategyInterface;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

abstract class Field
{
    protected $name;

    protected $table;

    /**
     * Serialization groups.
     *
     * @var array
     */
    protected $groups;

    public function __construct(Table $table, string $name)
    {
        $this->name  = $name;
        $this->table = $table;
    }

    public const TYPE_SIMPLE = '@simple';

    public const TYPE_ONE_TO_MANY = '@oneToMany';

    public const TYPE_MANY_TO_ONE = '@manyToOne';

    public const TYPE_MANY_TO_MANY = '@manyToMany';

    private const ALLOWED_TYPES = [
        self::TYPE_SIMPLE       => true,
        self::TYPE_ONE_TO_MANY  => true,
        self::TYPE_MANY_TO_ONE  => true,
        self::TYPE_MANY_TO_MANY => true,
    ];

    /**
     * @param Table                                                      $table
     * @param string                                                     $name
     * @param string                                                     $type
     * @param null|Table                                                 $relatedTable
     * @param null|FieldStrategy|JoinStrategyInterface|ReferenceStrategy $strategy
     * @param array                                                      $groups
     *
     * @return Field|ManyToManyField|ManyToOneField|OneToManyField|SimpleField
     */
    public static function create(Table $table, string $name, string $type, ?Table $relatedTable = null, ?JoinStrategyInterface $strategy = null, array $groups = Expose::DEFAULT_GROUPS): self
    {
        if (!isset(self::ALLOWED_TYPES[$type])) {
            throw new \InvalidArgumentException(self::class . ': Allowed types: ' . \implode(', ', \array_keys(self::ALLOWED_TYPES)));
        }

        switch ($type) {
            case self::TYPE_SIMPLE:
                return (new SimpleField($table, $name))->setGroups($groups);
            case self::TYPE_MANY_TO_ONE:
                return (new ManyToOneField($table, $name, $relatedTable, $strategy))->setGroups($groups);
            case self::TYPE_ONE_TO_MANY:
                return (new OneToManyField($table, $name, $relatedTable, $strategy))->setGroups($groups);
            case self::TYPE_MANY_TO_MANY:
                return (new ManyToManyField($table, $name, $strategy))->setGroups($groups);
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
     * @return Field
     */
    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }
}
