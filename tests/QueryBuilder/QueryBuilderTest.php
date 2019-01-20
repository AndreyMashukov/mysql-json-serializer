<?php

namespace Tests\Mash\QueryBuilder;

use Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Pair;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Reference;
use Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\Service\TableManager;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use Mash\MysqlJsonSerializer\Wrapper\Mapping;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    /** @var TableManager */
    private $tableManager;

    public function setUp()
    {
        $this->tableManager = $this->getMockBuilder(TableManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Should allow to build query.
     *
     * WITH SIMPLE FIELD.
     *
     * @group unit
     */
    public function testShouldAllowToBuildQuerySimple()
    {
        $table = (new Table('test_table', 'alias', 'id'))
            ->addSimpleField('id')
            ->addSimpleField('field')
        ;

        $mapping = new Mapping();
        $mapping
            ->addMap($table, 'id', 'my_id')
            ->addMap($table, 'field', 'my_field_name');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping), $this->tableManager);
        $sql     = $builder->jsonArray();
        $this->assertEquals(
            "SELECT JSON_ARRAYAGG(JSON_OBJECT('my_id',alias_res.id,'my_field_name',alias_res.field)) FROM (SELECT DISTINCT alias.* FROM test_table alias ) alias_res",
            $sql->getSql()
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
        $oneToManyTable = (new Table('advert_group', 'adg', 'adg_id'))
            ->addSimpleField('adg_id')
            ->addSimpleField('adg_name')
        ;

        $table = (new Table('estate', 'est', 'est_id'))
            ->addSimpleField('est_id')
            ->addSimpleField('est_name')
            ->addOneToManyField($oneToManyTable, 'advert_groups', new FieldStrategy('adg_estate'));

        $mapping = new Mapping();
        $mapping
            ->addMap($table, 'est_id', 'id')
            ->addMap($table, 'est_name', 'name')
            ->addMap($oneToManyTable, 'adg_id', 'id')
            ->addMap($oneToManyTable, 'adg_name', 'name');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping), $this->tableManager);
        $builder
            ->setOffset(2)
            ->setLimit(1);

        $expected = "/SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',est_res\\.est_id,'name',est_res\\.est_name,'advert_groups',\\(SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',adg_[a-z0-9]{5}\\.adg_id,'name',adg_[a-z0-9]{5}\\.adg_name\\)\\) FROM advert_group adg_[a-z0-9]{5} INNER JOIN estate est_[a-z0-9]{5} ON est_[a-z0-9]{5}\\.est_id = adg_[a-z0-9]{5}\\.adg_estate WHERE est_[a-z0-9]{5}\\.est_id = est_res\\.est_id\\)\\)\\) FROM \\(SELECT DISTINCT est\\.\\* FROM estate est  LIMIT 1 OFFSET 2\\) est_res/ui"
        ;

        $sql = $builder->jsonArray();
        $this->assertRegExp($expected, $sql->getSql());
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
        $manyToOneTable = (new Table('estate', 'est', 'est_id'))
            ->addSimpleField('est_id')
            ->addSimpleField('est_name')
        ;

        $table = (new Table('advert_group', 'adg', 'adg_id'))
            ->addSimpleField('adg_id')
            ->addSimpleField('adg_name')
            ->addManyToOneField($manyToOneTable, 'estate', new FieldStrategy('adg_estate'))
        ;

        $mapping = new Mapping();
        $mapping
            ->addMap($manyToOneTable, 'est_id', 'id')
            ->addMap($manyToOneTable, 'est_name', 'name')
            ->addMap($table, 'adg_id', 'id')
            ->addMap($table, 'adg_name', 'name');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping), $this->tableManager);
        $builder
            ->setOffset(2)
            ->setLimit(2);

        $expected = "/SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',adg_res\\.adg_id,'name',adg_res\\.adg_name,'estate',\\(SELECT JSON_OBJECT\\('id',est_[a-z0-9]{5}\\.est_id,'name',est_[a-z0-9]{5}\\.est_name\\) FROM estate est_[a-z0-9]{5} WHERE est_[a-z0-9]{5}\\.est_id = adg_res\\.adg_estate LIMIT 1\\)\\)\\) FROM \\(SELECT DISTINCT adg\\.\\* FROM advert_group adg  LIMIT 2 OFFSET 2\\) adg_res/ui";

        $sql = $builder->jsonArray();
        $this->assertRegExp($expected, $sql->getSql());
    }

    /**
     * Should allow to join tables.
     *
     * @group unit
     */
    public function testShouldAllowToJoinTables()
    {
        $house = (new Table('house', 'hou', 'hou_id'))
            ->addSimpleField('hou_id')
            ->addSimpleField('hou_zip')
        ;

        $address = (new Table('address', 'adr', 'adr_id'))
            ->addSimpleField('adr_id')
            ->addManyToOneField($house, 'house', new FieldStrategy('adr_house'))
        ;

        $advert = (new Table('advert', 'adv', 'adv_id'))
            ->addSimpleField('adv_id')
            ->addSimpleField('adv_type')
            ->addManyToOneField($address, 'address', new FieldStrategy('adv_address'))
        ;

        $contact = new Table('contact', 'cnt', 'cnt_id');
        $mapping = new Mapping();
        $mapping
            ->addMap($advert, 'adv_id', 'id')
            ->addMap($advert, 'adv_type', 'type')
            ->addMap($address, 'adr_id', 'id')
            ->addMap($contact, 'cnt_id', 'id')
            ->addMap($contact, 'cnt_type', 'type')
            ->addMap($house, 'hou_id', 'id')
            ->addMap($house, 'hou_zip', 'zip_code');

        $builder = new QueryBuilder($advert, new FieldWrapper($mapping), $this->tableManager);
        $builder
            ->innerJoin($contact, 'cnt.cnt_type = :type AND adv.adv_contact = cnt.cnt_id')
            ->setParameter('type', 'owner')
            ->setOffset(2)
            ->setLimit(2);

        $expected = "/SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',adv_res\\.adv_id,'type',adv_res\\.adv_type,'address',\\(SELECT JSON_OBJECT\\('id',adr_[a-z0-9]{5}\\.adr_id,'house',\\(SELECT JSON_OBJECT\\('id',hou_[a-z0-9]{5}\\.hou_id,'zip_code',hou_[a-z0-9]{5}\\.hou_zip\\) FROM house hou_[a-z0-9]{5} WHERE hou_[a-z0-9]{5}\\.hou_id = adr_[a-z0-9]{5}\\.adr_house LIMIT 1\\)\\) FROM address adr_[a-z0-9]{5} WHERE adr_[a-z0-9]{5}\\.adr_id = adv_res\\.adv_address LIMIT 1\\)\\)\\) FROM \\(SELECT DISTINCT adv\\.\\* FROM advert adv  INNER JOIN contact cnt ON cnt\\.cnt_type = :type AND adv\\.adv_contact = cnt\\.cnt_id LIMIT 2 OFFSET 2\\) adv_res/ui";

        $sql = $builder->jsonArray();
        $this->assertRegExp($expected, $sql->getSql());
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
        $house = (new Table('house', 'hou', 'hou_id'))
            ->addSimpleField('hou_id')
            ->addSimpleField('hou_zip')
        ;

        $address = (new Table('address', 'adr', 'adr_id'))
            ->addSimpleField('adr_id')
            ->addManyToOneField($house, 'house', new FieldStrategy('adr_house'))
        ;

        $advert = (new Table('advert', 'adv', 'adv_id'))
            ->addSimpleField('adv_id')
            ->addSimpleField('adv_type')
            ->addManyToOneField($address, 'address', new FieldStrategy('adv_address'))
        ;

        $contact = new Table('contact', 'cnt', 'cnt_id');
        $mapping = new Mapping();
        $mapping
            ->addMap($advert, 'adv_id', 'id')
            ->addMap($advert, 'adv_type', 'type')
            ->addMap($address, 'adr_id', 'id')
            ->addMap($contact, 'cnt_id', 'id')
            ->addMap($contact, 'cnt_type', 'type')
            ->addMap($house, 'hou_id', 'id')
            ->addMap($house, 'hou_zip', 'zip_code');

        $builder = new QueryBuilder($advert, new FieldWrapper($mapping), $this->tableManager);
        $builder
            ->innerJoin($contact, 'cnt.cnt_type = :type AND adv.adv_contact = cnt.cnt_id')
            ->innerJoin($address, 'adv.adv_address = adr.adr_id')
            ->innerJoin($house, 'hou.hou_zip > :minZip AND hou.hou_id = adr.adr_house')
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
            ->setParameter('minZip', 0)
            ->orderBy('adv.adv_id', 'DESC')
            ->groupBy('adv.adv_id')
        ;

        $params   = $builder->getParameters();
        $expected = "/SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',adv_res\\.adv_id,'type',adv_res\\.adv_type,'address',\\(SELECT JSON_OBJECT\\('id',adr_[a-z0-9]{5}\\.adr_id,'house',\\(SELECT JSON_OBJECT\\('id',hou_[a-z0-9]{5}\\.hou_id,'zip_code',hou_[a-z0-9]{5}\\.hou_zip\\) FROM house hou_[a-z0-9]{5} WHERE hou_[a-z0-9]{5}\\.hou_id = adr_[a-z0-9]{5}\\.adr_house LIMIT 1\\)\\) FROM address adr_[a-z0-9]{5} WHERE adr_[a-z0-9]{5}\\.adr_id = adv_res.adv_address LIMIT 1\\)\\)\\) FROM \\(SELECT DISTINCT adv\\.\\* FROM advert adv  INNER JOIN contact cnt ON cnt\\.cnt_type = :type AND adv\\.adv_contact = cnt\\.cnt_id INNER JOIN address adr ON adv\\.adv_address = adr\\.adr_id INNER JOIN house hou ON hou\\.hou_zip > :minZip AND hou\\.hou_id = adr\\.adr_house WHERE adv_id >= :minId AND adv_id <= :maxId OR adv_id = :id OR adv_id = :second GROUP BY adv\\.adv_id ORDER BY adv\\.adv_id DESC LIMIT 2 OFFSET 2\\) adv_res/ui";

        $expectedParams = [
            'type'   => 'owner',
            'minId'  => '2',
            'id'     => '1',
            'second' => '2',
            'maxId'  => '5',
            'minZip' => '0',
        ];

        $sql = $builder->jsonArray();
        $this->assertEquals($expectedParams, $params);
        $this->assertRegExp($expected, $sql->getSql());
    }

    /**
     * Should allow to get ManyToMany relation.
     *
     * @group unit
     */
    public function testManyToManyRelation()
    {
        $photo  = new Table('photo', 'pht', 'pht_id');
        $advert = (new Table('advert', 'adv', 'adv_id'))
            ->addSimpleField('adv_id')
            ->addSimpleField('adv_type')
        ;

        $reference = new Table('photo_xref', 'xrf');
        $strategy  = new ReferenceStrategy(
            new Reference(
                new Pair($advert, 'adv_id'),
                new Pair($reference, 'xref_adv_id')
            ),
            new Reference(
                new Pair($photo, 'pht_id'),
                new Pair($reference, 'xref_pht_id')
            )
        );

        $mapping = new Mapping();
        $mapping
            ->addMap($advert, 'adv_id', 'id')
            ->addMap($advert, 'adv_type', 'type')
            ->addMap($photo, 'pht_id', 'id')
            ->addMap($photo, 'pht_hash', 'hash')
        ;

        $builder = new QueryBuilder($advert, new FieldWrapper($mapping), $this->tableManager);
        $builder->setLimit(2);

        $photo
            ->addSimpleField('pht_id')
            ->addSimpleField('pht_hash')
        ;

        $advert->addManyToManyField($photo, 'photos', $strategy);

        $expected = "/SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',adv_res\\.adv_id,'type',adv_res\\.adv_type,'photos',\\(SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',pht_[a-z0-9]{5}\\.pht_id,'hash',pht_[a-z0-9]{5}\\.pht_hash\\)\\) FROM photo pht_[a-z0-9]{5} INNER JOIN photo_xref xrf_[a-z0-9]{5} ON pht_[a-z0-9]{5}\\.pht_id = xrf_[a-z0-9]{5}\\.xref_pht_id INNER JOIN advert adv_[a-z0-9]{5} ON adv_[a-z0-9]{5}\\.adv_id = xrf_[a-z0-9]{5}\\.xref_adv_id WHERE adv_[a-z0-9]{5}\\.adv_id = adv_res\\.adv_id\\)\\)\\) FROM \\(SELECT DISTINCT adv\\.\\* FROM advert adv  LIMIT 2\\) adv_res/ui";

        $sql = $builder->jsonArray();
        $this->assertRegExp($expected, $sql->getSql());
    }

    /**
     * Should allow to build query.
     *
     * OneToOne relation.
     *
     * @group unit
     */
    public function testShouldAllowToBuildQueryOneToOne()
    {
        $oneToOneTable = (new Table('page', 'pge', 'pge_id'))
            ->addSimpleField('pge_id')
            ->addSimpleField('pge_url')
        ;

        $table = (new Table('advert', 'adv', 'adv_id'))
            ->addSimpleField('adv_id')
            ->addSimpleField('adv_type')
            ->addManyToOneField($oneToOneTable, 'page', new FieldStrategy('adv_page'))
        ;

        $mapping = new Mapping();
        $mapping
            ->addMap($oneToOneTable, 'pge_id', 'id')
            ->addMap($oneToOneTable, 'pge_url', 'url')
            ->addMap($table, 'adv_id', 'id')
            ->addMap($table, 'adv_type', 'type');

        $builder = new QueryBuilder($table, new FieldWrapper($mapping), $this->tableManager);
        $builder
            ->setOffset(2)
            ->setLimit(2);

        $expected = "/SELECT JSON_ARRAYAGG\\(JSON_OBJECT\\('id',adv_res\\.adv_id,'type',adv_res\\.adv_type,'page',\\(SELECT JSON_OBJECT\\('id',pge_[a-z0-9]{5}\\.pge_id,'url',pge_[a-z0-9]{5}\\.pge_url\\) FROM page pge_[a-z0-9]{5} WHERE pge_[a-z0-9]{5}\\.pge_id = adv_res\\.adv_page LIMIT 1\\)\\)\\) FROM \\(SELECT DISTINCT adv\\.\\* FROM advert adv  LIMIT 2 OFFSET 2\\) adv_res/ui";

        $sql = $builder->jsonArray();
        $this->assertRegExp($expected, $sql->getSql());
    }
}
