<?php

namespace Tests\Mash\QueryBuilder;

use Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use Mash\MysqlJsonSerializer\Wrapper\Mapping;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    /**
     * Should allow to build query.
     *
     * WITH SIMPLE FIELD.
     *
     * @group unit
     */
    public function testShouldAllowToBuildQuerySimple()
    {
        $table   = new Table('test_table', 'alias', 'id');
        $mapping = new Mapping();
        $mapping
            ->addMap($table, 'id', 'my_id')
            ->addMap($table, 'field', 'my_field_name')
        ;

        $builder = new QueryBuilder($table, new FieldWrapper($mapping));

        $builder
            ->select()
            ->addSimpleField('id')
            ->addSimpleField('field')
        ;

        $sql = $builder->getSql();
        $this->assertEquals("SELECT JSON_OBJECT('my_id',alias.id,'my_field_name',alias.field) FROM test_table alias", $sql);
    }

    /**
     * Should allow to build query.
     *
     * OneToMany relation.
     *
     * @group unit
     */
    public function testShouldAllowToBuildQueryOneToMany()
    {
        $oneToManyTable = new Table('advert_group', 'adg', 'adg_id');
        $table          = new Table('estate', 'est', 'est_id');
        $mapping        = new Mapping();
        $mapping
            ->addMap($table, 'est_id', 'id')
            ->addMap($table, 'est_name', 'name')
            ->addMap($oneToManyTable, 'adg_id', 'id')
            ->addMap($oneToManyTable, 'adg_name', 'name')
        ;

        $builder = new QueryBuilder($table, new FieldWrapper($mapping));
        $builder
            ->select()
            ->addSimpleField('est_id')
            ->addSimpleField('est_name')
            ->setOffset(2)
            ->setLimit(1)
        ;

        $oneToManyField = $builder->addOneToManyField($oneToManyTable, 'advert_groups', 'adg_estate');
        $oneToManyField
            ->addSimpleField('adg_id')
            ->addSimpleField('adg_name')
        ;

        $expected = 'SELECT JSON_OBJECT('
            . "'id',est.est_id,"
            . "'name',est.est_name,"
            . "'advert_groups',JSON_ARRAY(("
            . "SELECT GROUP_CONCAT(JSON_OBJECT('id',adg.adg_id,'name',adg.adg_name)) "
            . 'FROM advert_group adg WHERE adg.adg_estate = est.est_id))) '
            . 'FROM estate est LIMIT 1 OFFSET 2';

        $sql = $builder->getSql();
        $this->assertEquals($expected, $sql);
    }
}
