[![Build Status](https://travis-ci.com/AndreyMashukov/mysql-json-serializer.svg?branch=master)](https://travis-ci.com/AndreyMashukov/mysql-json-serializer) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/AndreyMashukov/mysql-json-serializer/blob/master/LICENSE) [![Lasr: Release](https://img.shields.io/github/release/andreymashukov/mysql-json-serializer.svg)](https://github.com/AndreyMashukov/mysql-json-serializer/releases)

# mysql-json-serializer
This solution will help you to get serialized data from mysql

## About package
This package allows to get json objects already mapped by created mapping scheme. You will not lost time after query because you will get json from database. 

## Install
```bash
composer require mash/mysql-json-serializer
```

## Examples
You will be able to make easy difficult query and get already mapped data. For example I will show you some SQL queries which I created by this package.

#### Example 1 (SELECT JSON WITH OneToMany relation field)
```php
<?php

use \Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use \Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use \Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use \Mash\MysqlJsonSerializer\Wrapper\Mapping;
use \Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use \Mash\MysqlJsonSerializer\Service\TableManager;

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

$builder = new QueryBuilder($table, new FieldWrapper($mapping, new TableManager()));
$builder
    ->setOffset(2)
    ->setLimit(1);

$sql = $builder->jsonArray();
```

```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',est_res.est_id,'name',est_res.est_name,'advert_groups',IFNULL((SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adg_6dfcb.adg_id,'name',adg_6dfcb.adg_name)) FROM advert_group adg_6dfcb INNER JOIN estate est_6dd72 ON est_6dd72.est_id = adg_6dfcb.adg_estate WHERE est_6dd72.est_id = est_res.est_id), JSON_ARRAY()))) FROM (SELECT DISTINCT est.* FROM estate est  LIMIT 1 OFFSET 2) est_res;
```

`result`
```json
[{"id": 3, "name": "Москва, окская улица, 3к1", "advert_groups": [{"id": 10, "name": "avito-1115362430"}]}]
```

#### Example 2 (SELECT JSON WITH ManyToOne relation field)
```php
<?php

use \Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use \Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use \Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use \Mash\MysqlJsonSerializer\Wrapper\Mapping;
use \Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use \Mash\MysqlJsonSerializer\Service\TableManager;

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

$builder = new QueryBuilder($table, new FieldWrapper($mapping, new TableManager()));
$builder
    ->setOffset(2)
    ->setLimit(2);

$sql = $builder->jsonArray();
```

```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adg_res.adg_id,'name',adg_res.adg_name,'estate',(SELECT JSON_OBJECT('id',est_3364c.est_id,'name',est_3364c.est_name) FROM estate est_3364c WHERE est_3364c.est_id = adg_res.adg_estate LIMIT 1))) FROM (SELECT DISTINCT adg.* FROM advert_group adg  LIMIT 2 OFFSET 2) adg_res;
```
`result`
```json
[{"id": 3, "name": "avito-1139860625", "estate": {"id": 12, "name": "Москва, привольная улица, 1к1"}}, {"id": 4, "name": "avito-928780107", "estate": {"id": 10, "name": "Москва, новорязанская улица, 2/7, 6 этаж"}}]
```

#### Example 3 (SELECT JSON WITH ManyToMany relation field)
```php
<?php

use \Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Reference;
use \Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use \Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Pair;
use \Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use \Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use \Mash\MysqlJsonSerializer\Wrapper\Mapping;
use \Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use \Mash\MysqlJsonSerializer\Service\TableManager;

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

$builder = new QueryBuilder($advert, new FieldWrapper($mapping, new TableManager()));
$builder->setLimit(2);

$photo
    ->addSimpleField('pht_id')
    ->addSimpleField('pht_hash')
;

$advert->addManyToManyField($photo, 'photos', $strategy);

$sql = $builder->jsonArray();
```

```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adv_res.adv_id,'type',adv_res.adv_type,'photos',IFNULL((SELECT JSON_ARRAYAGG(JSON_OBJECT('id',pht_58725.pht_id,'hash',pht_58725.pht_hash)) FROM photo pht_58725 INNER JOIN photo_xref xrf_c84ca ON pht_58725.pht_id = xrf_c84ca.xref_pht_id INNER JOIN advert adv_251cf ON adv_251cf.adv_id = xrf_c84ca.xref_adv_id WHERE adv_251cf.adv_id = adv_res.adv_id), JSON_ARRAY()))) FROM (SELECT DISTINCT adv.* FROM advert adv  LIMIT 2) adv_res;
```
`result`
```json
[{"id": 1, "type": "rent", "photos": [{"id": 48, "hash": "670cee35bcd9f2fce30d36262d5a00a09f7eed6e530c1eda7e37759b5ec92127"}, {"id": 49, "hash": "df1bd52a3947bd7c40e24d8a09479bcf03b4a4de4b8d7006f31aeb2e5a4ec072"}, {"id": 50, "hash": "565d22c353929920e73fa963d068939bc63971892f6c08bad505c8ca4a072951"}, {"id": 51, "hash": "570759d2ede30785f8a1ab1b6981b4823d9e14d7f61db028cbe9888045623033"}, {"id": 52, "hash": "281b077d4c815a5ca2a990ac79842c2f27e4bc34bfcafc2c132838ffbfadec14"}, {"id": 53, "hash": "b6fe0c1574f79574c8ef4878d6987d5eb77a1e49cf8f604405a2b6ed94ba00b3"}, {"id": 54, "hash": "50ff677fdab88bc94e61f1d83a1acfc0b908344703c0381dd9c5d2939e115710"}, {"id": 55, "hash": "7d709fde26d09c17a94826d48f18b168bbdada8c23a7d1aec8bb2a3a8d7dba0c"}, {"id": 56, "hash": "4db70971c10cf3ddd9bfeb5d5fb5415ff20cf7d4dea99b39471957225903c7f1"}, {"id": 57, "hash": "38e4fa549336675026f70db88d76bfb5ea704deab32285b3aa1f27677133639d"}, {"id": 58, "hash": "5563bba1be98813d09828ffb8c79f4227e3ee791a167d383261bdb8c4d7a17a2"}, {"id": 59, "hash": "1a71f2ab8a50c9431a039aa7efeac763cef43ccebf349a90f267a6886d1eb5d0"}, {"id": 60, "hash": "d30d34459d4b1183c92dc560b4f51523a40dc8e85ed637810556c2045cb176fe"}, {"id": 61, "hash": "936776eac75caa4ad8e3536dac93e48aa08057a93b3c57c084542d0978f8277d"}, {"id": 62, "hash": "9b8ec038119f0ce4a85179883dcea911b1720d2491c2fb978e38d2a282f411b0"}, {"id": 63, "hash": "1713587d8d65c77033c4502189b53c3a256681f57aeeff5db874c54559157434"}]}, {"id": 2, "type": "rent", "photos": [{"id": 1, "hash": "4ca8626f5c590e064e91560a1e9fe14368aefe7f67fa899d2802bb42b72e899a"}, {"id": 2, "hash": "68c9a07dab79603c97d3329f6239ad9e60c3d07e83ae99ba7f01d39e580790b2"}, {"id": 3, "hash": "1751178a57a6c672b05b70a12e766aa164bd9d2570151e05fe5f227a21951b00"}, {"id": 4, "hash": "fa44238af38382b0b235fefc3c4150c82f12ab717ad2141417665497afe9714f"}, {"id": 5, "hash": "b348cae00df94d14e95beb61fc2c4585dd85fadb76987f745dffa5a96c77c9ae"}, {"id": 6, "hash": "12cff756bd497926b01285b5776209aaff89b1041d0ccc31fd2987e824747537"}, {"id": 7, "hash": "ef3645db4ad2ea59f2b18d7c2d7f144a16bf48ceff1520a8386b63a901caad98"}, {"id": 8, "hash": "4019e3b98957c602476202d69d08dd37570d86a151c35ee85f325975909408fd"}, {"id": 9, "hash": "4687b7d84f60dcca65e1d0eb048488d8676b938e8b941ac9e2793f16635600f8"}, {"id": 10, "hash": "364ce21028d7238d5be2fcc8679ba366286d31e53fb7f3d7d9be8d8163c68846"}, {"id": 11, "hash": "40de7b415e138730734c3ce4010700ecc6ed750edd83d1dd85210288747792f8"}, {"id": 12, "hash": "7108a0c3c4352cac0913136b71ac40121334ecd4ddd16750a649330b6d86b115"}, {"id": 13, "hash": "fa3165a9f8ebec61b52b5b3650d8629e1cab9c0d408345faad2f2f48114066d2"}, {"id": 14, "hash": "1c68dc14c47c0e78b4f4fa9a6a05159f1df8a40ae2b9550145d14c2ae48c9e7a"}, {"id": 15, "hash": "63d151901c62b3528ecf43f169879736c79e266c45c95cf63dc540a93644beae"}, {"id": 16, "hash": "437ba369bbf608282ac40b5f34886679de0b41d9d6537d62d647db07e77a118d"}, {"id": 17, "hash": "d36de7afce0d208e86c33ddb31b9e8f1f4a91613b8b11304d059cf7e980f101a"}, {"id": 18, "hash": "8bc8cdcb52a9eca4715a4d6b5c2ba152e2387caf0fddedad62e4b171bf2e2cc9"}, {"id": 19, "hash": "42a836111fe1ddf5d70d0ae79fd78257e67d5a1f919df46b41011a0b3420e4b6"}, {"id": 20, "hash": "9e47d9a3c39dcbe3a57839e31e4874b802d6e67b8e5fe560a876b464aae59ad2"}]}]
```

#### Example 4 (OneToOne relation)
```php
<?php

use \Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use \Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use \Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use \Mash\MysqlJsonSerializer\Wrapper\Mapping;
use \Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;
use \Mash\MysqlJsonSerializer\Service\TableManager;

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

$builder = new QueryBuilder($table, new FieldWrapper($mapping, new TableManager()));
$builder
    ->setOffset(2)
    ->setLimit(2);

$expected = "SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adv_res.adv_id,'type',adv_res.adv_type,'page',(SELECT JSON_OBJECT('id',pge.pge_id,'url',pge.pge_url) FROM page pge WHERE pge.pge_id = adv_res.adv_page LIMIT 1))) FROM (SELECT * FROM advert adv  LIMIT 2 OFFSET 2) adv_res";

$sql = $builder->jsonArray();
```
```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adv_res.adv_id,'type',adv_res.adv_type,'page',(SELECT JSON_OBJECT('id',pge_761e0.pge_id,'url',pge_761e0.pge_url) FROM page pge_761e0 WHERE pge_761e0.pge_id = adv_res.adv_page LIMIT 1))) FROM (SELECT DISTINCT adv.* FROM advert adv  LIMIT 2 OFFSET 2) adv_res;
```
`result`
```json
[{"id": 3, "page": {"id": 24, "url": "https://m.avito.ru/moskva/kvartiry/1-k_kvartira_45_m_717_et._1139860625"}, "type": "rent"}, {"id": 4, "page": {"id": 23, "url": "https://m.avito.ru/moskva/kvartiry/1-k_kvartira_40_m_66_et._928780107"}, "type": "rent"}]
```

## Symfony 4 (flex) Integration

#### Configure
Add this to services.yaml: 
```yaml
# MySQL Json Serializer
services:
    Mash\MysqlJsonSerializer\Service\ControllerListener: ~

    Mash\MysqlJsonSerializer\Service\ViewListener: ~

    Mash\MysqlJsonSerializer\Wrapper\Mapping: ~

    Mash\MysqlJsonSerializer\Wrapper\FieldWrapper: ~

    Mash\MysqlJsonSerializer\Service\QueryBuilderFactory: ~

    Mash\MysqlJsonSerializer\Service\KernelListener: ~

    Mash\MysqlJsonSerializer\Service\TableManager: ~
```

#### Setup your Entities
Below you will find information how to configure your entities for auto serialization

```php
<?php

namespace App\Entity;

use App\Entity\Location\City;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mash\MysqlJsonSerializer\Annotation as MysqlJSON;
use Mash\MysqlJsonSerializer\Annotation\Table;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="page", indexes={
 *     @ORM\Index(columns={"pge_status"}),
 *     @ORM\Index(columns={"pge_site"}),
 * })
 *
 * @ORM\Entity(repositoryClass="App\Repository\PageRepository")
 *
 * @Serializer\ExclusionPolicy(Serializer\ExclusionPolicy::ALL)
 *
 * @Table(alias="pge")
 */
class Page implements LockedResourceInterface
{
    const STATUS_PENDING = 0;

    const STATUS_NOT_PARSED = 1;

    const STATUS_PARSED = 2;

    const STATUS_INVALID = 3;

    public static $types = [
        self::STATUS_PENDING,
        self::STATUS_NOT_PARSED,
        self::STATUS_PARSED,
        self::STATUS_INVALID,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="pge_id", type="integer")
     *
     * @Serializer\Expose
     *
     * @MysqlJSON\Expose
     */
    private $id;

    /**
     * @ORM\Column(name="pge_status", type="integer")
     *
     * @Serializer\Expose
     *
     * @MysqlJSON\Expose
     */
    private $status;

    /**
     * @ORM\Column(name="pge_type", type="string")
     *
     * @Serializer\Expose
     *
     * @MysqlJSON\Expose
     */
    private $type;

    /**
     * @ORM\Column(name="pge_category", type="string")
     *
     * @Serializer\Expose
     *
     * @MysqlJSON\Expose
     */
    private $category;

    /**
     * @ORM\Column(name="pge_url", type="string")
     *
     * @Serializer\Expose
     *
     * @MysqlJSON\Expose
     */
    private $url;

    /**
     * One Page has one Lock.
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Lock\PageLock", mappedBy="resource")
     *
     * @Serializer\Expose
     *
     * @MysqlJSON\Expose
     */
    private $lock;

    /**
     * @ORM\Column(name="pge_body", type="blob", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     * @Serializer\AccessType("public_method")
     *
     * @Serializer\Groups(groups={"Default", "page_full"})
     *
     * @Assert\Type(type="string")
     *
     * @MysqlJSON\Expose(groups={"Default", "page_full"}, type="Mash\MysqlJsonSerializer\Wrapper\Type\Blob")
     */
    private $body;

    /**
     * Many Pages have one Style.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Style", inversedBy="pages", cascade={"persist"})
     * @ORM\JoinColumn(name="pge_style", referencedColumnName="stl_id", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Groups(groups={"page_full"})
     *
     * @MysqlJSON\Expose(groups={"page_full"})
     */
    private $style;

    /**
     * Many Pages have one City.
     *
     * @var City
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Location\City", cascade={"persist"})
     * @ORM\JoinColumn(name="pge_city", referencedColumnName="cit_id")
     *
     * @Serializer\Expose
     * @Serializer\Groups(groups={"page_full"})
     *
     * @Assert\NotBlank
     *
     * @MysqlJSON\Expose(groups={"page_full"})
     */
    private $city;

    /**
     * Many Pages have one Site.
     *
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Site", cascade={"persist"})
     * @ORM\JoinColumn(name="pge_site", referencedColumnName="site_id")
     *
     * @Serializer\Expose
     * @Serializer\Groups(groups={"page_full"})
     *
     * @Assert\NotBlank
     *
     * @MysqlJSON\Expose(groups={"page_full"})
     */
    private $site;

    //....
}
```
Look to example on top, it's really easy to configure the serialization, looks like using JMSSerializer, or Symfony serializer, when I was writing this feature, I use my experience in Symfony, and take only good things.

If you want to expose some field add `@MysqlJSON\Expose(groups={"page_full"})` and add ` @Rest\View(serializerGroups={"Default", "page_full"})` on controller method

`note`: If you use `@MysqlJSON\Expose` without setting groups, field will have `Default` group

Lets see, how to return result from controller

```php
<?php

namespace App\RestController;

use App\Annotation\Lock;
use App\Entity\Lock\PageLock;
use App\Entity\Page;
use App\Entity\Site;
use App\Form\PageType;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Mash\MysqlJsonSerializer\QueryBuilder\SQL\SQL;
use Mash\MysqlJsonSerializer\Service\QueryBuilderFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PageController.
 *
 * @Rest\RouteResource("page", pluralize=false)
 */
class PageController extends AbstractFOSRestController
{
    /**
     * @var QueryBuilderFactory
     */
    private $queryBuilderFactory;

    public function __construct(QueryBuilderFactory $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * Get Page list.
     *
     * @ApiDoc(
     *     views={"v1"},
     *     section="Page",
     *     description="Get Page list",
     *     filters={
     *         {
     *             "name": "page",
     *             "dataType": "integer",
     *             "required": "false",
     *             "description": "Page number for pagination"
     *         },
     *         {
     *             "name": "limit",
     *             "dataType": "integer",
     *             "required": "false",
     *             "description": "Limit for pagination"
     *         },
     *         {
     *             "name": "site",
     *             "dataType": "integer",
     *             "required": "false",
     *             "description": "Site ID, numeric site identifier"
     *         }
     *     },
     *     requirements={
     *         {
     *             "name": "version",
     *             "dataType": "string",
     *             "requirement": "(v1|v2|v3)",
     *             "description": "API version"
     *         },
     *     },
     *     statusCodes={
     *         "200": "Returned when successful",
     *         "403": "Returned when you haven't permissions",
     *     },
     *     tags={"v1"}
     * )
     *
     * @Rest\View(serializerGroups={"Default", "page_full"})
     *
     * @IsGranted("ROLE_HISTORY_VIEW", statusCode=403, message="Access denied, you have not permissions")
     *
     * @param Request $request
     *
     * @return SQL
     */
    public function getListAction(Request $request)
    {
        $page   = $request->get('page', 1);
        $limit  = $request->get('limit', 20);
        $siteId = $request->get('site', null);
        $status = $request->get('status', null);
        $noLock = $request->get('noLock', null);

        $builder = $this->queryBuilderFactory->getBuilder(Page::class);
        $builder->orderBy('pge.pge_id', 'DESC');

        if (null !== $status) {
            $builder
                ->andWhere('pge.pge_status = :status')
                ->setParameter('status', $status)
            ;
        }

        if (null !== $siteId && 0 !== (int) $siteId) {
            $builder
                ->innerJoin(Site::class, 'pge.pge_site = sit.site_id AND sit.site_id = :site')
                ->setParameter('site', $siteId);
        }

        if ('1' === $noLock) {
            $builder
                ->select('pge.*, lck.lck_id')
                ->leftJoin(
                    PageLock::class,
                    'pge.pge_id = lck.lck_resource AND lck.lck_type = :lock_type'
                )
                ->setParameter('lock_type', 'page')
                ->andWhere('lck.lck_id is NULL')
            ;
        }

        return $builder->jsonPagination($page, $limit);
    }

    /**
     * Get Page by ID.
     *
     * @ApiDoc(
     *     views={"v1"},
     *     section="Page",
     *     description="Get Page by ID",
     *     requirements={
     *         {
     *             "name": "page",
     *             "dataType": "integer",
     *             "requirement": "\d+",
     *             "description": "Page ID"
     *         },
     *         {
     *             "name": "version",
     *             "dataType": "string",
     *             "requirement": "(v1|v2|v3)",
     *             "description": "API version"
     *         },
     *     },
     *     statusCodes={
     *         "200": "Returned when successful",
     *         "404": "Returned when not found",
     *         "403": "Returned when you haven't permissions",
     *     },
     *     output="App\Entity\Page",
     *     tags={"v1"}
     * )
     *
     * @Rest\View(serializerGroups={"Default", "page_full"})
     *
     * @IsGranted("ROLE_HISTORY_VIEW", statusCode=403, message="Access denied, you have not permissions")
     *
     * @param Page $page
     *
     * @return SQL
     */
    public function getAction(Page $page)
    {
        $builder = $this->queryBuilderFactory->getBuilder(Page::class);

        return $builder->jsonObject($page->getId());
    }

    /**
     * Post new Page.
     *
     * @ApiDoc(
     *     views={"v1"},
     *     section="Page",
     *     description="Post new Page",
     *     statusCodes={
     *         "200": "Returned when successful",
     *         "404": "Returned when not found",
     *         "403": "Returned when you haven't permissions",
     *     },
     *     input="App\Form\PageType",
     *     output="App\Entity\Page",
     *     tags={"v1"}
     * )
     *
     * @Rest\View(serializerGroups={"Default", "page_full"})
     *
     * @IsGranted("ROLE_ADMIN", statusCode=403, message="Access denied, you have not permissions")
     *
     * @param Request $request
     *
     * @return array|SQL
     */
    public function postAction(Request $request)
    {
        $page = new Page();

        $form = $this->createForm(PageType::class, $page, ['method' => 'POST'])
            ->handleRequest($request);

        if (false === $form->isSubmitted()) {
            $form->submit([]);
        }

        if (false === $form->isValid()) {
            return ['form' => $form];
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($page);
        $manager->flush();

        $builder = $this->queryBuilderFactory->getBuilder(Page::class);

        return $builder->jsonObject($page->getId());
    }
    
    //...
}
```

Method `$builder->jsonPagination($page, $limit);` allows to paginate data into mysql, and return json with all relations, pages, total count and other pagination fields

Example:
```json
{"data": [{"id": 216, "url": "http://url", "body": "<html>Some html body</html>", "city": {"id": 41, "cad": "99", "name": "Name", "center": {"latitude": 33.4444, "longitude": 88.9999}, "region": {"id": 41, "cad": "12", "code": "99", "name": "region", "capital": null, "country": {"id": 38, "area": 17100000, "name": "Россия", "capital": null}}, "population": 10000}, "site": {"id": 22, "name": "site_name_7116", "address": "http://test.address"}, "type": "rent", "style": {"id": 21, "hash": "7698daff6f9d6a9947b7773fb3cb90a2b6b7d82238533b41958c047ee4427258"}, "status": 0, "category": "flat"}], "totalItems": 3, "currentPage": 1, "itemsPerPage": 20}
```
Also you can get the single serialized object by `$builder->jsonObject($page->getId());` method, it will return serialized object

Example:
```json
{"id": 216, "url": "http://url", "body": "<html>Some html body</html>", "city": {"id": 41, "cad": "99", "name": "Name", "center": {"latitude": 33.4444, "longitude": 88.9999}, "region": {"id": 41, "cad": "12", "code": "99", "name": "region", "capital": null, "country": {"id": 38, "area": 17100000, "name": "Россия", "capital": null}}, "population": 10000}, "site": {"id": 22, "name": "site_name_7116", "address": "http://test.address"}, "type": "rent", "style": {"id": 21, "hash": "7698daff6f9d6a9947b7773fb3cb90a2b6b7d82238533b41958c047ee4427258"}, "status": 0, "category": "flat"}
```

You can use your CustomTypes for implementation you custom serialization logic, for example, by default MySQL serialize blob as base64 decoded value in JSON, but if we add `@MysqlJSON\Expose(groups={"Default", "page_full"}, type="Mash\MysqlJsonSerializer\Wrapper\Type\Blob")` annotation, FieldWrapper will use this type in serialization and return text.

Example of CustomType:
```php
<?php

namespace Mash\MysqlJsonSerializer\Wrapper\Type;

class Blob implements CustomTypeInterface
{
    public function convert(string $name, string $alias): string
    {
        return "CONVERT({$alias}.{$name} USING utf8mb4)";
    }
}
```

It's easy to write your custom type, just implement interface `CustomTypeInterface` and use MySQL functions into.

## Updates

`v. 2.0.5`

Now you can create virtual properties, it allows to avoid database denormalization and keep relation model.

example of Entity configuration, just add map to class doc comment.
```php


/**
 * @ORM\Entity(repositoryClass="App\Repository\EstateRepository")
 *
 * @Serializer\ExclusionPolicy(Serializer\ExclusionPolicy::ALL)
 *
 * @Table(alias="est", map={
 *     "last_update": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[updated_at]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MAX,
 *         "groups": {"estate_public_list"},
 *     },
 *     "max_rooms": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[rooms]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MAX,
 *         "groups": {"estate_public_list"},
 *     },
 *     "min_rooms": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[rooms]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MIN,
 *         "groups": {"estate_public_list"},
 *     },
 *     "max_area": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[area]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MAX,
 *         "groups": {"estate_public_list"},
 *     },
 *     "min_area": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[area]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MIN,
 *         "groups": {"estate_public_list"},
 *     },
 *     "max_sell_price": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[price]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MAX,
 *         "groups": {"estate_public_list"},
 *         "filter": {"type": "sell"},
 *     },
 *     "min_sell_price": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[price]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MIN,
 *         "groups": {"estate_public_list"},
 *         "filter": {"type": "sell"},
 *     },
 *     "max_rent_price": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[price]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MAX,
 *         "groups": {"estate_public_list"},
 *         "filter": {"type": "rent"},
 *     },
 *     "min_rent_price": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[price]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MIN,
 *         "groups": {"estate_public_list"},
 *         "filter": {"type": "rent"},
 *     },
 *     "address": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[address]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_FIRST,
 *         "orderBy": "id",
 *         "groups": {"estate_public_list"},
 *     },
 *     "advert_count": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[id]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_COUNT,
 *         "groups": {"estate_public_list"},
 *     },
 *     "rent_description": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[description]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_FIRST,
 *         "groups": {"estate_public_list"},
 *         "filter": {"type": "rent"},
 *     },
 *     "sell_description": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[description]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_FIRST,
 *         "groups": {"estate_public_list"},
 *         "filter": {"type": "sell"},
 *     },
 *     "type": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[type]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_FIRST,
 *         "groups": {"estate_public_list"},
 *     },
 *     "category": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[category]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_FIRST,
 *         "groups": {"estate_public_list"},
 *     },
 *     "daily": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert[daily]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_MAX,
 *         "groups": {"estate_public_list"},
 *     },
 *     "sell_contacts_ids": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert.App\Entity\Contact[id]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_COLLECTION,
 *         "groups": {"estate_public_list"},
 *         "filter": {"App\Entity\Advert[type]": "sell"},
 *     },
 *     "rent_contacts_ids": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert.App\Entity\Contact[id]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_COLLECTION,
 *         "groups": {"estate_public_list"},
 *         "filter": {"App\Entity\Advert[type]": "rent"},
 *     },
 *     "photo_files_ids": {
 *         "route": "App\Entity\AdvertGroup.App\Entity\Advert.App\Entity\Photo.App\Entity\GoogleFile[file_id]",
 *         "type": Mash\MysqlJsonSerializer\QueryBuilder\Field\JoinField::TYPE_COLLECTION,
 *         "groups": {"estate_public_list"},
 *     },
 * })
 */
class Estate
{
}
 ```

result:
```json
{"id":5,"name":"\u041c\u043e\u0441\u043a\u0432\u0430, \u043e\u043d\u0435\u0436\u0441\u043a\u0430\u044f, 53\u043a1, 20","type":"rent","daily":0,"likes":[],"address":{"house":{"living_area":null,"nearby_stations":[{"id":13,"route":{"legs":[{"distance":{"text":"2,6 \u043a\u043c","value":2619},"duration":{"text":"21 \u043c\u0438\u043d.","value":1283}}]},"station":{"id":26,"name":"\u0420\u0435\u0447\u043d\u043e\u0439 \u0432\u043e\u043a\u0437\u0430\u043b","metro_line":{"name":"\u0417\u0430\u043c\u043e\u0441\u043a\u0432\u043e\u0440\u0435\u0446\u043a\u0430\u044f","color":"4FB04F"}},"distance":1989.125241883},{"id":14,"route":{"legs":[{"distance":{"text":"2,9 \u043a\u043c","value":2881},"duration":{"text":"20 \u043c\u0438\u043d.","value":1190}}]},"station":{"id":251,"name":"\u041a\u043e\u043f\u0442\u0435\u0432\u043e","metro_line":{"name":"\u041c\u043e\u0441\u043a\u043e\u0432\u0441\u043a\u043e\u0435 \u0446\u0435\u043d\u0442\u0440\u0430\u043b\u044c\u043d\u043e\u0435 \u043a\u043e\u043b\u044c\u0446\u043e","color":"F9BCD1"}},"distance":2371.3891511315},{"id":15,"route":{"legs":[{"distance":{"text":"5,9 \u043a\u043c","value":5867},"duration":{"text":"39 \u043c\u0438\u043d.","value":2350}}]},"station":{"id":189,"name":"\u0421\u0435\u043b\u0438\u0433\u0435\u0440\u0441\u043a\u0430\u044f","metro_line":{"name":"\u041b\u044e\u0431\u043b\u0438\u043d\u0441\u043a\u043e-\u0414\u043c\u0438\u0442\u0440\u043e\u0432\u0441\u043a\u0430\u044f","color":"BED12C"}},"distance":2744.2158722766},{"id":16,"route":{"legs":[{"distance":{"text":"13,0 \u043a\u043c","value":12962},"duration":{"text":"57 \u043c\u0438\u043d.","value":3399}}]},"station":{"id":155,"name":"\u0421\u0445\u043e\u0434\u043d\u0435\u043d\u0441\u043a\u0430\u044f","metro_line":{"name":"\u0422\u0430\u0433\u0430\u043d\u0441\u043a\u043e-\u041a\u0440\u0430\u0441\u043d\u043e\u043f\u0440\u0435\u0441\u043d\u0435\u043d\u0441\u043a\u0430\u044f","color":"943E90"}},"distance":4295.9764504959},{"id":17,"route":{"legs":[{"distance":{"text":"9,4 \u043a\u043c","value":9379},"duration":{"text":"41 \u043c\u0438\u043d.","value":2451}}]},"station":{"id":133,"name":"\u041f\u0435\u0442\u0440\u043e\u0432\u0441\u043a\u043e-\u0420\u0430\u0437\u0443\u043c\u043e\u0432\u0441\u043a\u0430\u044f","metro_line":{"name":"\u0421\u0435\u0440\u043f\u0443\u0445\u043e\u0432\u0441\u043a\u043e-\u0422\u0438\u043c\u0438\u0440\u044f\u0437\u0435\u0432\u0441\u043a\u0430\u044f","color":"ADACAC"}},"distance":4987.258303491}]},"district":{"name":"\u0445\u043e\u0432\u0440\u0438\u043d\u043e"}},"category":"flat","max_area":46,"min_area":20,"max_rooms":1,"min_rooms":1,"last_update":"2019-06-29 18:08:54.000000","advert_count":36,"max_rent_price":19958,"max_sell_price":null,"min_rent_price":15117,"min_sell_price":null,"photo_files_ids":["1W3V8mi8SG9mXUueo8w43OPMlnFmxrebC","1Kdut7RBron3PUaOI4w2tEfSKpmKs1oub","1SnIEsNdDKFc03LOQljh-2lFl1-yYkkOr","1GHsZVe2cjS4J1_HnPC-9P1iRz-FE-yUy","1ldZD5lPa0pW1SKcMU_3RE86kHLUFyiJN"],"rent_description":"Eligendi qui ducimus porro delectus.Atque voluptates enim quia quis quam.Tempore sunt quidem eligendi similique.Omnis cumque earum repellat.Nihil non culpa iste ut est nobis.Harum eveniet dolorem recusandae et.Qui officia eaque sunt est perferendis magni ut illum.Officiis ea aut sint quisquam.Eligendi nulla consectetur inventore.Numquam eveniet eligendi aliquid aperiam.Est facere doloremque dolorem sint quis et.Non cupiditate non ut ut porro illum et.Beatae nemo aut iusto dicta asperiores ut fuga.Voluptas sed aut qui corrupti.Libero quis repellendus illum cum voluptatem non sit.Voluptatem perspiciatis tempore unde odio repellendus quisquam sit.Non deleniti eaque et modi fugiat qui et rem.Et ratione a enim.Illo sint quo aut aut sint provident.Sit hic magni a ut sapiente et ea et.","sell_description":null,"rent_contacts_ids":[29,7,20,31,23,15,11,18,25],"sell_contacts_ids":[]}
```
