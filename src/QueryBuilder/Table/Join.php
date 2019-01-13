<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Table;

abstract class Join
{
    public const TYPE_INNER = '@inner';

    public const TYPE_LEFT = '@left';

    public const ALLOWED_TYPES = [
        self::TYPE_INNER => true,
        self::TYPE_LEFT  => true,
    ];

    /** @var Table */
    private $joinTable;

    /** @var string */
    private $condition;

    public function __construct(Table $table, string $condition)
    {
        $this->condition = $condition;
        $this->joinTable = $table;
    }

    public static function create(Table $table, string $type, string $condition): self
    {
        if (!isset(self::ALLOWED_TYPES[$type])) {
            $message = self::class
                . ': This type is not allowed, allowed types are: '
                . \implode(',', \array_keys(self::ALLOWED_TYPES));

            throw new \InvalidArgumentException($message);
        }

        switch ($type) {
            case self::TYPE_LEFT:
                return new LeftJoin($table, $condition);
            case self::TYPE_INNER:
                return new InnerJoin($table, $condition);
        }
    }

    public function __toString()
    {
        $expression = $this->getType()
            . ' '
            . $this->joinTable->getName()
            . ' '
            . $this->joinTable->getAlias()
            . ' ON '
            . $this->condition;

        return $expression;
    }

    abstract protected function getType(): string;
}
