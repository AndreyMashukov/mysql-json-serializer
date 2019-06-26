<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

class ManyToManyField extends CollectionField implements RelationInterface
{
    public function __construct(Table $table, string $name, ReferenceStrategy $strategy)
    {
        parent::__construct($table, $name, $strategy);
    }
}
