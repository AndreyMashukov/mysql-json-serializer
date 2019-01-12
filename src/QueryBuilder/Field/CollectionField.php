<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\JoinStrategyInterface;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\FieldManage;
use Mash\MysqlJsonSerializer\QueryBuilder\Traits\TableManage;

abstract class CollectionField extends Field
{
    use FieldManage;

    use TableManage;

    protected $strategy;

    public function __construct(Table $table, string $name, JoinStrategyInterface $strategy)
    {
        parent::__construct($table, $name);

        $this->parameters = [];
        $this->fieldList  = new FieldCollection();
        $this->strategy   = $strategy;
    }

    /**
     * @return JoinStrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
