<?php

namespace Mash\MysqlJsonSerializer\Service;

use Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;

class QueryBuilderFactory
{
    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     * @var FieldWrapper
     */
    private $fieldWrapper;

    public function __construct(TableManager $tableManager, FieldWrapper $fieldWrapper)
    {
        $this->tableManager = $tableManager;
        $this->fieldWrapper = $fieldWrapper;
    }

    public function getBuilder(string $entity): QueryBuilder
    {
        if (!\class_exists($entity)) {
            throw new \InvalidArgumentException('Invalid entity');
        }

        $table = $this->tableManager->getTable($entity);

        if (!$table) {
            throw new \RuntimeException('Table is not on control. Please add @Table annotation.');
        }

        return new QueryBuilder($table, $this->fieldWrapper);
    }
}
