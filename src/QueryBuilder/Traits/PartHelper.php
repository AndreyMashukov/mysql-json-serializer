<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Traits;

use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\AndWhere;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\OrWhere;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Condition\Where;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;

trait PartHelper
{
    private function getJoins(Table $table): string
    {
        $joins = $table->getJoins()->getElements();

        if (0 === \count($joins)) {
            return '';
        }

        $part = ' ';

        foreach ($joins as $join) {
            $part .= $join;
        }

        return $part;
    }

    private function getWhere(Table $table): string
    {
        $where = $table->getWhere()->getElements();

        if (0 === \count($where)) {
            return '';
        }

        $part     = '';
        $iterator = 0;

        /** @var AndWhere|OrWhere|Where $item */
        foreach ($where as $item) {
            if (0 === $iterator) {
                $part .= $item;

                ++$iterator;
                continue;
            }

            if ($item instanceof OrWhere) {
                $part .= ' OR ' . $item;

                ++$iterator;
                continue;
            }

            $part .= ' AND ' . $item;

            ++$iterator;
        }

        return $part;
    }
}
