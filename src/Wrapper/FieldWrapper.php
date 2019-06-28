<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\Annotation\Expose;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\CollectionField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToOneField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToOneField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\RelationInterface;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\SimpleField;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\PartHelper;
use Mash\MysqlJsonSerializer\Service\TableManager;

/**
 * Class FieldWrapper.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FieldWrapper
{
    use PartHelper;

    public const ERROR_CAN_NOT_PARSE_COLLECTION_SQL = 2;

    public const ERROR_INVALID_MAP = 4;

    public const UNLIMITED_DEPTH = 0;

    public const JOIN_FIELD_MAX_DEPTH = 1;

    public const MANY_TO_MANY_MAX_DEPTH = 1;

    public const MANY_TO_ONE_MAX_DEPTH = 1;

    public const ONE_TO_MANY_MAX_DEPTH = 1;

    public const ONE_TO_ONE_MAX_DEPTH = 1;

    private $cache = [];

    private $mapping;

    private $groups = Expose::DEFAULT_GROUPS;

    private $tableManager;

    public function __construct(Mapping $mapping, TableManager $tableManager)
    {
        $this->mapping      = $mapping;
        $this->tableManager = $tableManager;
    }

    public function select(Table $table, string $aliasSuffix): string
    {
        $this->cache = [];

        return $this->wrap($table->getFieldList(), $aliasSuffix);
    }

    /**
     * @param FieldCollection $collection
     * @param string          $aliasSuffix
     *
     * @return string
     */
    public function wrap(FieldCollection $collection, string $aliasSuffix = ''): string
    {
        $parts = [];

        /** @var Field $item */
        foreach ($collection->getElements() as $item) {
            $part = $this->wrapField($item, $aliasSuffix);

            if ('' === $part) {
                continue;
            }

            $parts[] = $part;
        }

        $data = \implode(',', $parts);

        return "JSON_OBJECT({$data})";
    }

    /**
     * @param Field|RelationInterface $field
     * @param string                  $aliasSuffix
     * @param \Closure                $join
     * @param \Closure                $where
     * @param \Closure                $groupBy
     * @param \Closure                $orderBy
     *
     * @return string
     */
    private function wrapField(Field $field, string $aliasSuffix = '', \Closure $join = null, \Closure $where = null, \Closure $groupBy = null, \Closure $orderBy = null): string
    {
        $key = $this->getKey($field);

        $fieldGroups = $field->getGroups();
        $intersect   = \array_intersect($fieldGroups, $this->groups);

        if (0 === \count($intersect)) {
            return '';
        }

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = 0;
        }

        $maxDepth = $this->getDepth($field);

        if ($this->cache[$key] >= $maxDepth && self::UNLIMITED_DEPTH !== $maxDepth) {
            return '';
        }

        ++$this->cache[$key];

        if ($field instanceof SimpleField) {
            $result = $this->wrapSimple($field, $aliasSuffix);

            return $result;
        }

        if ($field instanceof JoinField) {
            $result = $this->wrapJoin($field, $aliasSuffix);

            return $result;
        }

        return "'" . $this->mapping->getAlias($field) . "'," . $this->subSelect($field, $aliasSuffix, $join, $where, $groupBy, $orderBy);
    }

    private function wrapJoin(JoinField $field, string $suffix): string
    {
        $steps = [$field->getTable()];

        foreach ($field->getRoute() as $entityClass) {
            $steps[] = $this->tableManager->getTable($entityClass);
        }

        /** @var Table $last */
        $last     = \end($steps);
        $relation = $last->getFieldList()->getByName($field->getProperty());

        if (JoinField::TYPE_FIRST === $field->getType() && $relation) {
            return $this->wrapRelation($field, $steps, $suffix);
        }

        if (JoinField::TYPE_COLLECTION === $field->getType() && $relation) {
            return $this->wrapRelation($field, $steps, $suffix);
        }

        // ToDo add support of simple field collection...

        return "'{$field->getName()}', ({$this->getJoinFieldSelect($steps, $field, $suffix)} LIMIT 1)";
    }

    private function wrapRelation(JoinField $field, array $steps, string $masterSuffix): string
    {
        /** @var Table $last */
        $last         = \end($steps);
        $uniqueSuffix = \mb_substr(\md5(\uniqid()), 0, 5);

        $join    = $last->getFieldList()->getByName($field->getProperty());
        $steps[] = $join->getTable();

        $joinClosure = function (string $uniqSuffix) use ($steps) {
            $joins = $this->getJoins($steps, $uniqSuffix);

            return ' ' . \implode(' ', $joins) . ' ';
        };

        $orderBy      = $this->getTableColumn($last, $field->getOrderBy());
        $whereClosure = function (string $uniqSuffix) use ($masterSuffix, $field, $orderBy, $last) {
            $filter    = $this->getFilter($field, $uniqSuffix, $last);
            $filterSql = ' ';

            if ([] !== $filter) {
                $filterSql .= 'AND ' . \implode(' AND ', $filter) . ' ';
            }

            return "WHERE {$this->getJoinWhere($field, $uniqSuffix, $masterSuffix)}{$filterSql}";
        };

        $groupClosure = null;

        if ($join instanceof ManyToManyField) {
            $groupClosure = function (string $uniqSuffix) use ($last) {
                return "GROUP BY {$last->getAlias()}_{$uniqSuffix}.{$last->getIdField()}";
            };
        }

        $orderByClosure = function (string $uniqSuffix) use ($last, $orderBy) {
            return "ORDER BY {$last->getAlias()}_{$uniqSuffix}.{$orderBy} ASC";
        };

        $join->setGroups($field->getGroups());
        $join->setName($field->getName());

        $sql = $this->wrapField($join, "_{$uniqueSuffix}", $joinClosure, $whereClosure, $groupClosure, $orderByClosure);

        if (!($join instanceof ManyToOneField && JoinField::TYPE_COLLECTION === $field->getType()) || $join instanceof ManyToManyField) {
            return $sql;
        }

        // ToDo weak place, need to improve in future
        \preg_match('/,\(SELECT\s+JSON_OBJECT\((?P<json_object>.+)\)\s+(?P<sql>.+)\s+LIMIT 1\)/ui', $sql, $matches);

        if ([] === $matches) {
            throw new \RuntimeException('Can\'t parse collection sql.', self::ERROR_CAN_NOT_PARSE_COLLECTION_SQL);
        }

        return "'{$field->getName()}',(SELECT IFNULL((SELECT JSON_ARRAYAGG(JSON_OBJECT({$matches['json_object']})) {$matches['sql']}), JSON_ARRAY()))";
    }

    /**
     * @param array     $steps
     * @param JoinField $field
     * @param string    $suffix
     *
     * @return string
     */
    private function getJoinFieldSelect(array $steps, JoinField $field, string $suffix): string
    {
        $uniqueSuffix = \mb_substr(\md5(\uniqid()), 0, 5);

        $sql = 'SELECT ';

        /** @var Table $lastStep */
        $lastStep  = \end($steps);
        $mainAlias = $lastStep->getAlias();

        $value  = $this->getTableColumn($lastStep, $field->getProperty());
        $sql   .= '';

        $fieldType = $field->getType();

        if (JoinField::TYPE_FIRST !== $fieldType && !$value) {
            throw new \InvalidArgumentException('Invalid map, please provide field in [] or change field type.', self::ERROR_INVALID_MAP);
        }

        if (JoinField::TYPE_MAX === $fieldType) {
            $sql .= "MAX({$mainAlias}_{$uniqueSuffix}.{$value}) ";
        }

        if (JoinField::TYPE_MIN === $fieldType) {
            $sql .= "MIN({$mainAlias}_{$uniqueSuffix}.{$value}) ";
        }

        if (JoinField::TYPE_COUNT === $fieldType) {
            $sql .= "COUNT({$mainAlias}_{$uniqueSuffix}.{$value}) ";
        }

        if (JoinField::TYPE_FIRST === $fieldType) {
            $sql .= "{$mainAlias}_{$uniqueSuffix}.{$value} ";
        }

        $joins = $this->getJoins($steps, $uniqueSuffix);

        $sql .= "FROM {$lastStep->getName()} {$mainAlias}_{$uniqueSuffix} " . \implode(' ', $joins) . ' ';
        $sql .= "WHERE {$this->getJoinWhere($field, $uniqueSuffix, $suffix)}";

        $filter = $this->getFilter($field, $uniqueSuffix, $lastStep);

        if ([] !== $filter) {
            $sql .= ' AND ' . \implode(' AND ', $filter);
        }

        $orderBy = $this->getTableColumn($lastStep, $field->getOrderBy());

        return $sql . " ORDER BY {$lastStep->getAlias()}_{$uniqueSuffix}.{$orderBy} ASC";
    }

    private function getFilter(JoinField $field, string $uniqueSuffix, Table $table): array
    {
        $filter = [];

        foreach ($field->getFilter() as $property => $value) {
            $filter[] = "{$table->getAlias()}_{$uniqueSuffix}.{$this->getTableColumn($table, $property)} = '{$value}'";
        }

        return $filter;
    }

    private function getJoinWhere(JoinField $field, string $uniqueSuffix, string $masterSuffix): string
    {
        $table = $field->getTable();

        $fieldTableAleas = $table->getAlias();

        return "{$fieldTableAleas}_{$uniqueSuffix}.{$table->getIdField()} = {$fieldTableAleas}{$masterSuffix}.{$table->getIdField()}";
    }

    private function getJoins(array $steps, string $uniqueSuffix): array
    {
        $joins = [];
        $steps = \array_reverse($steps);

        /**
         * @var int
         * @var Table $step
         */
        foreach ($steps as $key => $step) {
            $next = $steps[$key + 1] ?? null;

            if (!$next) {
                continue;
            }

            $nextJoin = $this->getNextJoin($step, $next);

            if ($nextJoin instanceof ManyToManyField) {
                $joins[] = $this->getManyToManyJoin($nextJoin, $uniqueSuffix);

                continue;
            }

            $joins[] = "INNER JOIN {$next->getName()} {$next->getAlias()}_{$uniqueSuffix} ON {$this->getCondition($nextJoin, $uniqueSuffix)}";

            continue;
        }

        return $joins;
    }

    private function getManyToManyJoin(Field $field, string $suffix): string
    {
        $strategy        = $field->getStrategy()->getStrategy();
        $main            = $strategy->getFirst()->getFirst();
        $mainRef         = $strategy->getFirst()->getSecond();
        $collection      = $strategy->getSecond()->getFirst();
        $collectionXref  = $strategy->getSecond()->getSecond();

        $collectionXrefTable = $collectionXref->getTable();

        return "INNER JOIN {$collectionXrefTable->getName()} {$collectionXrefTable->getAlias()}_{$suffix} "
            . "ON {$collection->getTable()->getAlias()}_{$suffix}.{$collection->getField()} = "
            . "{$collectionXrefTable->getAlias()}_{$suffix}.{$collectionXref->getField()} "
            . "INNER JOIN {$main->getTable()->getName()} {$main->getTable()->getAlias()}_{$suffix} "
            . "ON {$main->getTable()->getAlias()}_{$suffix}.{$main->getTable()->getIdField()} = {$mainRef->getTable()->getAlias()}_{$suffix}.{$mainRef->getField()}";
    }

    private function getCondition(Field $field, string $suffix): string
    {
        /** @var Table $parent */
        $parent = $field->getParent();
        $table  = $field->getTable();

        $strategy = $field->getStrategy();
        $explode  = \explode('_', $strategy);

        // ToDo fix it in future...
        if ($table->getAlias() === $explode[0]) {
            return "{$table->getAlias()}_{$suffix}.{$strategy->getStrategy()} = {$parent->getAlias()}_{$suffix}.{$parent->getIdField()}";
        }

        return "{$parent->getAlias()}_{$suffix}.{$strategy->getStrategy()} = {$table->getAlias()}_{$suffix}.{$table->getIdField()}";
    }

    private function getTableColumn(Table $table, ?string $property): ?string
    {
        if (!$property) {
            return null;
        }

        $map = \array_flip($this->mapping->getTableMap($table));

        return $map[$property] ?? $property;
    }

    private function getNextJoin(Table $next, Table $step): ?CollectionField
    {
        /** @var CollectionField|Field $item */
        foreach ($step->getFieldList()->getElements() as $item) {
            if (!$item instanceof CollectionField) {
                continue;
            }

            if ($item->getTable() !== $next) {
                continue;
            }

            return $item;
        }

        return null;
    }

    /**
     * @param SimpleField $field
     * @param string      $aliasSuffix
     *
     * @return string
     */
    private function wrapSimple(SimpleField $field, string $aliasSuffix): string
    {
        $type = $field->getType();

        if ($type) {
            return "'" . $this->mapping->getAlias($field)
                . "'," . $type->convert($field->getName(), "{$field->getTable()->getAlias()}{$aliasSuffix}");
        }

        return "'" . $this->mapping->getAlias($field) . "',"
            . "{$field->getTable()->getAlias()}{$aliasSuffix}.{$field->getName()}";
    }

    /**
     * @param RelationInterface $field
     * @param string            $aliasSuffix
     * @param \Closure          $join
     * @param \Closure          $where
     * @param \Closure          $groupBy
     * @param \Closure          $orderBy
     *
     * @return string
     */
    private function subSelect(
        RelationInterface $field,
        string $aliasSuffix = '',
        \Closure $join = null,
        \Closure $where = null,
        \Closure $groupBy = null,
        \Closure $orderBy = null
    ): string {
        if ($field instanceof OneToManyField) {
            return $this->getOneToMany($field, $aliasSuffix);
        }

        if ($field instanceof ManyToOneField) {
            return $this->getManyToOne($field, $aliasSuffix, $join, $where);
        }

        if ($field instanceof OneToOneField) {
            return $this->getOneToOne($field, $aliasSuffix);
        }

        return $this->getManyToMany($field, $aliasSuffix, $join, $where, $groupBy, $orderBy); // ToDo add group by in others in future
    }

    private function getOneToMany(OneToManyField $field, string $aliasSuffix): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        $uniqSuffix     = \mb_substr(\md5(\uniqid()), 0, 5);
        $uniqSuffixMain = \mb_substr(\md5(\uniqid()), 0, 5);

        $sql   = "IFNULL((SELECT JSON_ARRAYAGG({$this->wrap($field->getFieldList(), '_' . $uniqSuffixMain)}) "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffixMain} "
            . "INNER JOIN {$parent->getName()} {$parent->getAlias()}_{$uniqSuffix} ON {$parent->getAlias()}_{$uniqSuffix}.{$parent->getIdField()} = {$table->getAlias()}_{$uniqSuffixMain}.{$field->getStrategy()} "
            . "WHERE {$parent->getAlias()}_{$uniqSuffix}.{$parent->getIdField()} = {$parent->getAlias()}{$aliasSuffix}.{$parent->getIdField()}"
            . '), JSON_ARRAY())'
        ;

        return $sql;
    }

    private function getManyToOne(ManyToOneField $field, string $aliasSuffix = '', \Closure $join = null, \Closure $where = null): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        $uniqSuffix = \mb_substr(\md5(\uniqid()), 0, 5);

        $whereCondition = "WHERE {$table->getAlias()}_{$uniqSuffix}.{$table->getIdField()} = {$parent->getAlias()}{$aliasSuffix}.{$field->getStrategy()}";

        if ($where) {
            $whereCondition = "{$where($uniqSuffix)}";
        }

        $sql = '('
            . "SELECT {$this->wrap($field->getFieldList(), '_' . $uniqSuffix)} "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffix} " . ($join ? $join($uniqSuffix) : '')
            . $whereCondition
            . ' '
            . 'LIMIT 1)'
        ;

        return $sql;
    }

    private function getOneToOne(OneToOneField $field, string $aliasSuffix = ''): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        $uniqSuffix = \mb_substr(\md5(\uniqid()), 0, 5);

        $sql   = '('
            . "SELECT {$this->wrap($field->getFieldList(), '_' . $uniqSuffix)} "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffix} "
            . "WHERE {$table->getAlias()}_{$uniqSuffix}.{$table->getIdField()} = {$parent->getAlias()}{$aliasSuffix}.{$field->getStrategy()}"
            . ' '
            . 'LIMIT 1)'
        ;

        return $sql;
    }

    /**
     * @param ManyToManyField $field
     * @param string          $aliasSuffix
     * @param null|\Closure   $join
     * @param null|\Closure   $where
     * @param null|\Closure   $groupBy
     * @param null|\Closure   $orderBy
     *
     * @return string
     */
    private function getManyToMany(ManyToManyField $field, string $aliasSuffix, \Closure $join = null, \Closure $where = null, \Closure $groupBy = null, \Closure $orderBy = null): string
    {
        $table = $field->getTable();
        /** @var ReferenceStrategy $strategy */
        $strategy       = $field->getStrategy()->getStrategy();
        $main           = $strategy->getFirst()->getFirst();
        $mainRef        = $strategy->getFirst()->getSecond();
        $collection     = $strategy->getSecond()->getFirst();
        $collectionXref = $strategy->getSecond()->getSecond();

        $uniqSuffixRef  = \mb_substr(\md5(\uniqid()), 0, 5);
        $uniqSuffix     = \mb_substr(\md5(\uniqid()), 0, 5);
        $uniqSuffixMain = \mb_substr(\md5(\uniqid()), 0, 5);

        $whereCondition = "WHERE {$main->getTable()->getAlias()}_{$uniqSuffix}.{$main->getTable()->getIdField()} = "
            . "{$main->getTable()->getAlias()}{$aliasSuffix}.{$main->getTable()->getIdField()}";

        if ($where) {
            $whereCondition = "{$where($uniqSuffixMain)}";
        }

        $inner = "INNER JOIN {$collectionXref->getTable()->getName()} {$collectionXref->getTable()->getAlias()}_{$uniqSuffixRef} "
            . "ON {$collection->getTable()->getAlias()}_{$uniqSuffixMain}.{$collection->getField()} = "
            . "{$collectionXref->getTable()->getAlias()}_{$uniqSuffixRef}.{$collectionXref->getField()} "
            . "INNER JOIN {$main->getTable()->getName()} {$main->getTable()->getAlias()}_{$uniqSuffix} "
            . "ON {$main->getTable()->getAlias()}_{$uniqSuffix}.{$main->getTable()->getIdField()} = {$mainRef->getTable()->getAlias()}_{$uniqSuffixRef}.{$mainRef->getField()} ";

        if ($join) {
            $inner = $join($uniqSuffixMain) . ' ';
        }

        $sql = "IFNULL((SELECT JSON_ARRAYAGG({$this->wrap($field->getFieldList(), '_' . $uniqSuffixMain)}) "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffixMain} "
            . $inner
            . $whereCondition
            . ($groupBy ? " {$groupBy($uniqSuffixMain)} " : '')
            . ($orderBy ? " {$orderBy($uniqSuffixMain)} " : '')
            . ($groupBy ? ' LIMIT 1' : '')
            . '), JSON_ARRAY())'
        ;

        return $sql;
    }

    private function getDepth(Field $field): int
    {
        if ($field instanceof ManyToManyField) {
            return self::MANY_TO_MANY_MAX_DEPTH;
        }

        if ($field instanceof ManyToOneField) {
            return self::MANY_TO_ONE_MAX_DEPTH;
        }

        if ($field instanceof OneToManyField) {
            return self::ONE_TO_MANY_MAX_DEPTH;
        }

        if ($field instanceof OneToOneField) {
            return self::ONE_TO_ONE_MAX_DEPTH;
        }

        if ($field instanceof JoinField) {
            return self::JOIN_FIELD_MAX_DEPTH;
        }

        return self::UNLIMITED_DEPTH;
    }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    private function getKey(Field $field): string
    {
        $table = $field->getTable();
        $base  = "{$table->getAlias()}_{$field->getName()}";

        if ($field instanceof ManyToOneField) {
            return "{$field->getParent()->getAlias()}_{$base}";
        }

        if ($field instanceof OneToOneField) {
            return "{$field->getParent()->getAlias()}_{$base}";
        }

        return $base;
    }
}
