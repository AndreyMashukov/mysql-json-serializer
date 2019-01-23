<?php

namespace Mash\MysqlJsonSerializer\Wrapper\Type;

interface CustomTypeInterface
{
    public function convert(string $name, string $alias): string;
}
