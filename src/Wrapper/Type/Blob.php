<?php

namespace Mash\MysqlJsonSerializer\Wrapper\Type;

class Blob implements CustomTypeInterface
{
    public function convert(string $name, string $alias): string
    {
        return "CONVERT({$alias}.{$name} USING utf8mb4)";
    }
}
