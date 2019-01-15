<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\SQL;

interface SQLProviderInterface
{
    /**
     * Native sql.
     *
     * @return string
     */
    public function getSql(): string;

    /**
     * [key => value] parameters array.
     *
     * @return array
     */
    public function getParameters(): array;
}
