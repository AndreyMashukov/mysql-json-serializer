<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 1/15/19
 * Time: 8:06 PM.
 */

namespace Mash\MysqlJsonSerializer\QueryBuilder\SQL;

class JsonPagination extends SQL
{
    private $sql;

    private $countSql;

    /** @var int */
    private $itemsPerPage;

    /** @var int */
    private $currentPage;

    public function __construct(array $parameters, string $sql, string $countSql, int $itemsPerPage, int $currentPage)
    {
        parent::__construct($parameters);

        $this->sql          = $sql;
        $this->countSql     = $countSql;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage  = $currentPage;
    }

    public function getSql(): string
    {
        return 'SELECT JSON_OBJECT('
            . "'itemsPerPage',{$this->itemsPerPage},"
            . "'currentPage',{$this->currentPage},"
            . "'totalItems',({$this->countSql}),"
            . "'data',({$this->sql})"
            . ')'
        ;
    }
}
