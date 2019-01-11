<?php

namespace Tests\Mash\QueryBuilder;

use Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use Mash\MysqlJsonSerializer\Wrapper\Mapping;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    /**
     * Should allow to build query.
     *
     * @group unit
     */
    public function testShouldAllowToBuildQuery()
    {
        $mapping = new Mapping();
        $mapping
            ->addMap('id', 'my_id')
            ->addMap('field', 'my_field_name')
        ;

        $builder = new QueryBuilder('test_table', 'alias', new FieldWrapper($mapping));

        $builder
            ->select()
            ->addSimpleField('id')
            ->addSimpleField('field')
        ;

        $sql = $builder->getSql();
        $this->assertEquals("SELECT JSON_OBJECT(alias.id,'my_id',alias.field,'my_field_name') FROM test_table alias", $sql);
    }
}
