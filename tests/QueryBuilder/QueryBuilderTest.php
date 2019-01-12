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
            ->addMap($table, 'field', 'my_field_name');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping));

        $builder
            ->select()
            ->addSimpleField('id')
            ->addSimpleField('field');

        $sql = $builder->getSql();
        $this->assertEquals(
            "SELECT JSON_OBJECT('my_id',alias.id,'my_field_name',alias.field) FROM test_table alias",
            $sql
        );
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
            ->addMap($oneToManyTable, 'adg_name', 'name');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping));
        $builder
            ->select()
            ->addSimpleField('est_id')
            ->addSimpleField('est_name')
            ->setOffset(2)
            ->setLimit(1);

        $oneToManyField = $builder->addOneToManyField($oneToManyTable, 'advert_groups', 'adg_estate');
        $oneToManyField
            ->addSimpleField('adg_id')
            ->addSimpleField('adg_name');

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

    /**
     * Should allow to build query.
     *
     * ManyToOne relation.
     *
     * @group unit
     */
    public function testShouldAllowToBuildQueryManyToOne()
    {
        $manyToOneTable = new Table('estate', 'est', 'est_id');
        $table          = new Table('advert_group', 'adg', 'adg_id');
        $mapping        = new Mapping();
        $mapping
            ->addMap($manyToOneTable, 'est_id', 'id')
            ->addMap($manyToOneTable, 'est_name', 'name')
            ->addMap($table, 'adg_id', 'id')
            ->addMap($table, 'adg_name', 'name');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping));
        $builder
            ->select()
            ->addSimpleField('adg_id')
            ->addSimpleField('adg_name')
            ->setOffset(2)
            ->setLimit(2);

        $manyToOneField = $builder->addManyToOneField($manyToOneTable, 'estate', 'adg_estate');
        $manyToOneField
            ->addSimpleField('est_id')
            ->addSimpleField('est_name');

        $expected = 'SELECT JSON_OBJECT('
            . "'id',adg.adg_id,"
            . "'name',adg.adg_name,"
            . "'estate',(SELECT JSON_OBJECT("
            . "'id',est.est_id,"
            . "'name',est.est_name"
            . ') '
            . 'FROM estate est '
            . 'WHERE est.est_id = adg.adg_estate LIMIT 1)) '
            . 'FROM advert_group adg LIMIT 2 OFFSET 2';

        $sql = $builder->getSql();
        $this->assertEquals($expected, $sql);
    }

    /**
     * Should allow to join tables.
     *
     * @group unit
     */
    public function testShouldAllowToJoinTables()
    {
        $advert  = new Table('advert', 'adv', 'adv_id');
        $address = new Table('address', 'adr', 'adr_id');
        $contact = new Table('contact', 'cnt', 'cnt_id');
        $house   = new Table('house', 'hou', 'hou_id');
        $mapping = new Mapping();
        $mapping
            ->addMap($advert, 'adv_id', 'id')
            ->addMap($advert, 'adv_type', 'type')
            ->addMap($address, 'adr_id', 'id')
            ->addMap($contact, 'cnt_id', 'id')
            ->addMap($contact, 'cnt_type', 'type')
            ->addMap($house, 'hou_id', 'id')
            ->addMap($house, 'hou_zip', 'zip_code');

        $builder = new QueryBuilder($advert, new FieldWrapper($mapping));
        $builder
            ->select()
            ->addSimpleField('adv_id')
            ->addSimpleField('adv_type')
            ->innerJoin($contact, 'cnt_type = :type')
            ->setParameter('type', 'owner')
            ->setOffset(2)
            ->setLimit(2);

        $addressField = $builder->addManyToOneField($address, 'address', 'adv_address');
        $addressField
            ->addSimpleField('adr_id')
            ->addManyToOneField($house, 'house', 'adr_house')// will return house field
            ->addSimpleField('hou_id')
            ->addSimpleField('hou_zip');

        $expected = 'SELECT JSON_OBJECT('
            . "'id',adv.adv_id,"
            . "'type',adv.adv_type,"
            . "'address',("
            . 'SELECT JSON_OBJECT('
            . "'id',adr.adr_id,"
            . "'house',("
            . 'SELECT JSON_OBJECT('
            . "'id',hou.hou_id,"
            . "'zip_code',hou.hou_zip"
            . ') '
            . 'FROM house hou '
            . 'WHERE hou.hou_id = adr.adr_house LIMIT 1)) '
            . 'FROM address adr '
            . 'WHERE adr.adr_id = adv.adv_address LIMIT 1)) '
            . 'FROM advert adv INNER JOIN contact ON cnt_type = :type LIMIT 2 OFFSET 2';

        $sql = $builder->getSql();
        $this->assertEquals($expected, $sql);
        // result example:
        // {"id": 1, "type": "rent", "address": {"id": 1, "house": {"id": 1, "zip_code": "125565"}}}
    }

    /**
     * Should allow to add where conditions.
     *
     * @group unit
     */
    public function testShouldAllowToAddWhereConditions()
    {
        $advert  = new Table('advert', 'adv', 'adv_id');
        $address = new Table('address', 'adr', 'adr_id');
        $contact = new Table('contact', 'cnt', 'cnt_id');
        $house   = new Table('house', 'hou', 'hou_id');
        $mapping = new Mapping();
        $mapping
            ->addMap($advert, 'adv_id', 'id')
            ->addMap($advert, 'adv_type', 'type')
            ->addMap($address, 'adr_id', 'id')
            ->addMap($contact, 'cnt_id', 'id')
            ->addMap($contact, 'cnt_type', 'type')
            ->addMap($house, 'hou_id', 'id')
            ->addMap($house, 'hou_zip', 'zip_code');

        $builder = new QueryBuilder($advert, new FieldWrapper($mapping));
        $builder
            ->select()
            ->addSimpleField('adv_id')
            ->addSimpleField('adv_type')
            ->innerJoin($contact, 'cnt_type = :type')
            ->setParameter('type', 'owner')
            ->setOffset(2)
            ->setLimit(2)
            ->andWhere('adv_id >= :minId')
            ->andWhere('adv_id <= :maxId')
            ->orWhere('adv_id = :id')
            ->orWhere('adv_id = :second')
            ->setParameter('minId', 2)
            ->setParameter('id', 1)
            ->setParameter('second', 2)
            ->setParameter('maxId', 5)
            ->orderBy('adv.adv_id', 'DESC')
            ->groupBy('adv.adv_id')
        ;

        $addressField = $builder->addManyToOneField($address, 'address', 'adv_address');
        $addressField
            ->addSimpleField('adr_id')
            ->addManyToOneField($house, 'house', 'adr_house')// will return house field
            ->andWhere('hou.hou_zip > :minZip')
            ->setParameter('minZip', 0)
            ->addSimpleField('hou_id')
            ->addSimpleField('hou_zip');

        $params   = $builder->getParameters();
        $expected = 'SELECT JSON_OBJECT('
            . "'id',adv.adv_id,"
            . "'type',adv.adv_type,"
            . "'address',("
            . 'SELECT JSON_OBJECT('
            . "'id',adr.adr_id,"
            . "'house',("
            . 'SELECT JSON_OBJECT('
            . "'id',hou.hou_id,"
            . "'zip_code',hou.hou_zip) "
            . 'FROM house hou '
            . 'WHERE hou.hou_id = adr.adr_house AND (hou.hou_zip > :minZip) LIMIT 1)) '
            . 'FROM address adr '
            . 'WHERE adr.adr_id = adv.adv_address LIMIT 1)) '
            . 'FROM advert adv '
            . 'INNER JOIN contact ON cnt_type = :type '
            . 'WHERE adv_id >= :minId AND adv_id <= :maxId OR adv_id = :id OR adv_id = :second '
            . 'GROUP BY adv.adv_id ORDER BY adv.adv_id DESC LIMIT 2 OFFSET 2';

        $expectedParams = [
            'type'   => 'owner',
            'minId'  => '2',
            'id'     => '1',
            'second' => '2',
            'maxId'  => '5',
            'minZip' => '0',
        ];

        $sql = $builder->getSql();
        $this->assertEquals($expectedParams, $params);
        $this->assertEquals($expected, $sql);
    }
}
