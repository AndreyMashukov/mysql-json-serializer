<?php

namespace Mash\MysqlJsonSerializer\Wrapper\Type;

class Location implements CustomTypeInterface
{
    public function convert(string $name, string $alias): string
    {
        return '(SELECT CAST('
            . 'REPLACE('
            . 'REPLACE('
            . 'REPLACE('
            . "ASTEXT({$alias}.{$name}), 'POINT(', '{\"longitude\":'),"
            . "' ', ',\"latitude\":')"
            . ",')', '}') "
            . 'as JSON))';
    }
}
