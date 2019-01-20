<?php

namespace Mash\MysqlJsonSerializer\Wrapper;

use Mash\MysqlJsonSerializer\Annotation\Expose;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\Field;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\FieldCollection;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\ManyToOneField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToManyField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\OneToOneField;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\RelationInterface;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\SimpleField;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\PartHelper;

class FieldWrapper
{
    use PartHelper;

    public const UNLIMITED_DEPTH = 0;

    public const MANY_TO_MANY_MAX_DEPTH = 1;

    public const MANY_TO_ONE_MAX_DEPTH = 1;

    public const ONE_TO_MANY_MAX_DEPTH = 1;

    private $cache = [];

    private $mapping;

    private $groups = Expose::DEFAULT_GROUPS;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
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
     * @param Field  $field
     * @param string $aliasSuffix
     *
     * @return string
     */
    private function wrapField(Field $field, string $aliasSuffix = ''): string
    {
        $table = $field->getTable();
        $key   = "{$table->getAlias()}_{$field->getName()}";

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
            return "'" . $this->mapping->getAlias($field) . "',"
                . "{$field->getTable()->getAlias()}{$aliasSuffix}.{$field->getName()}";
        }

        return "'" . $this->mapping->getAlias($field) . "'," . $this->subSelect($field, $aliasSuffix);
    }

    /**
     * @param RelationInterface $field
     * @param string            $aliasSuffix
     *
     * @return string
     */
    private function subSelect(RelationInterface $field, string $aliasSuffix = ''): string
    {
        if ($field instanceof OneToManyField) {
            return $this->getOneToMany($field, $aliasSuffix);
        }

        if ($field instanceof ManyToOneField) {
            return $this->getManyToOne($field, $aliasSuffix);
        }

        if ($field instanceof OneToOneField) {
            return $this->getOneToOne($field, $aliasSuffix);
        }

        return $this->getManyToMany($field, $aliasSuffix);
    }

    private function getOneToMany(OneToManyField $field, string $aliasSuffix): string
    {
        $parent = $field->getParent();
        $table  = $field->getTable();

        $uniqSuffix     = \mb_substr(\md5(\uniqid()), 0, 5);
        $uniqSuffixMain = \mb_substr(\md5(\uniqid()), 0, 5);

        $where = $this->getWhere($table);
        $sql   = "(SELECT JSON_ARRAYAGG({$this->wrap($field->getFieldList(), '_' . $uniqSuffixMain)}) "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffixMain} "
            . $this->getJoins($table)
            . "INNER JOIN {$parent->getName()} {$parent->getAlias()}_{$uniqSuffix} ON {$parent->getAlias()}_{$uniqSuffix}.{$parent->getIdField()} = {$table->getAlias()}_{$uniqSuffixMain}.{$field->getStrategy()} "
            . "WHERE {$parent->getAlias()}_{$uniqSuffix}.{$parent->getIdField()} = {$parent->getAlias()}{$aliasSuffix}.{$parent->getIdField()}"
            . ('' === $where ? '' : " AND ({$where})")
            . ')'
        ;

        return $sql;
    }

    private function getManyToOne(ManyToOneField $field, string $aliasSuffix = ''): string
    {
        $child = $field->getChild();
        $table = $field->getTable();

        $uniqSuffix = \mb_substr(\md5(\uniqid()), 0, 5);

        $where = $this->getWhere($table);
        $sql   = '('
            . "SELECT {$this->wrap($field->getFieldList(), '_' . $uniqSuffix)} "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffix} "
            . $this->getJoins($table)
            . "WHERE {$table->getAlias()}_{$uniqSuffix}.{$table->getIdField()} = {$child->getAlias()}{$aliasSuffix}.{$field->getStrategy()}"
            . ('' === $where ? '' : " AND ({$where})")
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

        $where = $this->getWhere($table);
        $sql   = '('
            . "SELECT {$this->wrap($field->getFieldList(), '_' . $uniqSuffix)} "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffix} "
            . $this->getJoins($table)
            . "WHERE {$table->getAlias()}_{$uniqSuffix}.{$table->getIdField()} = {$parent->getAlias()}{$aliasSuffix}.{$field->getStrategy()}"
            . ('' === $where ? '' : " AND ({$where})")
            . ' '
            . 'LIMIT 1)'
        ;

        return $sql;
    }

    private function getManyToMany(ManyToManyField $field, string $aliasSuffix): string
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

        $where = $this->getWhere($table);
        $sql   = "(SELECT JSON_ARRAYAGG({$this->wrap($field->getFieldList(), '_' . $uniqSuffixMain)}) "
            . "FROM {$table->getName()} {$table->getAlias()}_{$uniqSuffixMain} "
            . $this->getJoins($table) . ' '
            . "INNER JOIN {$collectionXref->getTable()->getName()} {$collectionXref->getTable()->getAlias()}_{$uniqSuffixRef} "
            . "ON {$collection->getTable()->getAlias()}_{$uniqSuffixMain}.{$collection->getField()} = "
            . "{$collectionXref->getTable()->getAlias()}_{$uniqSuffixRef}.{$collectionXref->getField()} "
            . "INNER JOIN {$main->getTable()->getName()} {$main->getTable()->getAlias()}_{$uniqSuffix} "
            . "ON {$main->getTable()->getAlias()}_{$uniqSuffix}.{$main->getTable()->getIdField()} = {$mainRef->getTable()->getAlias()}_{$uniqSuffixRef}.{$mainRef->getField()} "
            . "WHERE {$main->getTable()->getAlias()}_{$uniqSuffix}.{$main->getTable()->getIdField()} = "
            . "{$main->getTable()->getAlias()}{$aliasSuffix}.{$main->getTable()->getIdField()}"
            . ('' === $where ? '' : " AND ({$where})")
            . ')'
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

        return self::UNLIMITED_DEPTH;
    }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }
}
