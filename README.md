[![Build Status](https://travis-ci.com/AndreyMashukov/mysql-json-serializer.svg?branch=master)](https://travis-ci.com/AndreyMashukov/mysql-json-serializer) [![MIT Licence](https://badges.frapsoft.com/os/mit/mit.svg?v=103)](https://opensource.org/licenses/mit-license.php)

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

$builder = new QueryBuilder($table, new FieldWrapper($mapping));
$builder
    ->setOffset(2)
    ->setLimit(1);

$sql = $builder->jsonArray();
```

```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',est_res.est_id,'name',est_res.est_name,'advert_groups',(SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adg.adg_id,'name',adg.adg_name)) FROM advert_group adg INNER JOIN estate est_2 ON est_2.est_id = adg.adg_estate WHERE est_2.est_id = est_res.est_id))) FROM (SELECT * FROM estate est  LIMIT 1 OFFSET 2) est_res
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

$builder = new QueryBuilder($table, new FieldWrapper($mapping));
$builder
    ->setOffset(2)
    ->setLimit(2);

$sql = $builder->jsonArray();
```

```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adg_res.adg_id,'name',adg_res.adg_name,'estate',(SELECT JSON_OBJECT('id',est.est_id,'name',est.est_name) FROM estate est WHERE est.est_id = adg_res.adg_estate LIMIT 1))) FROM (SELECT * FROM advert_group adg  LIMIT 2 OFFSET 2) adg_res;
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

$builder = new QueryBuilder($advert, new FieldWrapper($mapping));
$builder->setLimit(2);

$photo
    ->addSimpleField('pht_id')
    ->addSimpleField('pht_hash')
;

$advert->addManyToManyField($photo, 'photos', $strategy);

$sql = $builder->jsonArray();
```

```sql
SELECT JSON_ARRAYAGG(JSON_OBJECT('id',adv_res.adv_id,'type',adv_res.adv_type,'photos',(SELECT JSON_ARRAYAGG(JSON_OBJECT('id',pht.pht_id,'hash',pht.pht_hash)) FROM photo pht  INNER JOIN photo_xref xrf ON pht.pht_id = xrf.xref_pht_id INNER JOIN advert adv_2 ON adv_2.adv_id = xrf.xref_adv_id WHERE adv_2.adv_id = adv_res.adv_id))) FROM (SELECT * FROM advert adv  LIMIT 2) adv_res
```
`result`
```json
[{"id": 1, "type": "rent", "photos": [{"id": 48, "hash": "670cee35bcd9f2fce30d36262d5a00a09f7eed6e530c1eda7e37759b5ec92127"}, {"id": 49, "hash": "df1bd52a3947bd7c40e24d8a09479bcf03b4a4de4b8d7006f31aeb2e5a4ec072"}, {"id": 50, "hash": "565d22c353929920e73fa963d068939bc63971892f6c08bad505c8ca4a072951"}, {"id": 51, "hash": "570759d2ede30785f8a1ab1b6981b4823d9e14d7f61db028cbe9888045623033"}, {"id": 52, "hash": "281b077d4c815a5ca2a990ac79842c2f27e4bc34bfcafc2c132838ffbfadec14"}, {"id": 53, "hash": "b6fe0c1574f79574c8ef4878d6987d5eb77a1e49cf8f604405a2b6ed94ba00b3"}, {"id": 54, "hash": "50ff677fdab88bc94e61f1d83a1acfc0b908344703c0381dd9c5d2939e115710"}, {"id": 55, "hash": "7d709fde26d09c17a94826d48f18b168bbdada8c23a7d1aec8bb2a3a8d7dba0c"}, {"id": 56, "hash": "4db70971c10cf3ddd9bfeb5d5fb5415ff20cf7d4dea99b39471957225903c7f1"}, {"id": 57, "hash": "38e4fa549336675026f70db88d76bfb5ea704deab32285b3aa1f27677133639d"}, {"id": 58, "hash": "5563bba1be98813d09828ffb8c79f4227e3ee791a167d383261bdb8c4d7a17a2"}, {"id": 59, "hash": "1a71f2ab8a50c9431a039aa7efeac763cef43ccebf349a90f267a6886d1eb5d0"}, {"id": 60, "hash": "d30d34459d4b1183c92dc560b4f51523a40dc8e85ed637810556c2045cb176fe"}, {"id": 61, "hash": "936776eac75caa4ad8e3536dac93e48aa08057a93b3c57c084542d0978f8277d"}, {"id": 62, "hash": "9b8ec038119f0ce4a85179883dcea911b1720d2491c2fb978e38d2a282f411b0"}, {"id": 63, "hash": "1713587d8d65c77033c4502189b53c3a256681f57aeeff5db874c54559157434"}]}, {"id": 2, "type": "rent", "photos": [{"id": 1, "hash": "4ca8626f5c590e064e91560a1e9fe14368aefe7f67fa899d2802bb42b72e899a"}, {"id": 2, "hash": "68c9a07dab79603c97d3329f6239ad9e60c3d07e83ae99ba7f01d39e580790b2"}, {"id": 3, "hash": "1751178a57a6c672b05b70a12e766aa164bd9d2570151e05fe5f227a21951b00"}, {"id": 4, "hash": "fa44238af38382b0b235fefc3c4150c82f12ab717ad2141417665497afe9714f"}, {"id": 5, "hash": "b348cae00df94d14e95beb61fc2c4585dd85fadb76987f745dffa5a96c77c9ae"}, {"id": 6, "hash": "12cff756bd497926b01285b5776209aaff89b1041d0ccc31fd2987e824747537"}, {"id": 7, "hash": "ef3645db4ad2ea59f2b18d7c2d7f144a16bf48ceff1520a8386b63a901caad98"}, {"id": 8, "hash": "4019e3b98957c602476202d69d08dd37570d86a151c35ee85f325975909408fd"}, {"id": 9, "hash": "4687b7d84f60dcca65e1d0eb048488d8676b938e8b941ac9e2793f16635600f8"}, {"id": 10, "hash": "364ce21028d7238d5be2fcc8679ba366286d31e53fb7f3d7d9be8d8163c68846"}, {"id": 11, "hash": "40de7b415e138730734c3ce4010700ecc6ed750edd83d1dd85210288747792f8"}, {"id": 12, "hash": "7108a0c3c4352cac0913136b71ac40121334ecd4ddd16750a649330b6d86b115"}, {"id": 13, "hash": "fa3165a9f8ebec61b52b5b3650d8629e1cab9c0d408345faad2f2f48114066d2"}, {"id": 14, "hash": "1c68dc14c47c0e78b4f4fa9a6a05159f1df8a40ae2b9550145d14c2ae48c9e7a"}, {"id": 15, "hash": "63d151901c62b3528ecf43f169879736c79e266c45c95cf63dc540a93644beae"}, {"id": 16, "hash": "437ba369bbf608282ac40b5f34886679de0b41d9d6537d62d647db07e77a118d"}, {"id": 17, "hash": "d36de7afce0d208e86c33ddb31b9e8f1f4a91613b8b11304d059cf7e980f101a"}, {"id": 18, "hash": "8bc8cdcb52a9eca4715a4d6b5c2ba152e2387caf0fddedad62e4b171bf2e2cc9"}, {"id": 19, "hash": "42a836111fe1ddf5d70d0ae79fd78257e67d5a1f919df46b41011a0b3420e4b6"}, {"id": 20, "hash": "9e47d9a3c39dcbe3a57839e31e4874b802d6e67b8e5fe560a876b464aae59ad2"}]}]
```
## Symfony 4 (flex) Integration

#### Configure
Add this to services.yaml: 
```yaml
# MySQL Json Serializer
services:
    Mash\MysqlJsonSerializer\Wrapper\Mapping: ~

    Mash\MysqlJsonSerializer\Wrapper\FieldWrapper: ~

    Mash\MysqlJsonSerializer\Service\QueryBuilderFactory: ~

    Mash\MysqlJsonSerializer\Service\KernelListener: ~

    Mash\MysqlJsonSerializer\Service\TableManager: ~
```
