# mysql-json-serializer
This solution will help you to get serialized data from mysql

## About package
This package allows to get json objects already mapped by created mapping scheme. You will not lost time after query because you will get json from database. 

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

$oneToManyField = $builder->addOneToManyField($oneToManyTable, 'advert_groups', new FieldStrategy('adg_estate'));
$oneToManyField
    ->addSimpleField('adg_id')
    ->addSimpleField('adg_name');

$sql = $builder->getSql();
```

```sql
SELECT JSON_OBJECT('id',est.est_id,'name',est.est_name,'advert_groups',JSON_ARRAY((SELECT GROUP_CONCAT(JSON_OBJECT('id',adg.adg_id,'name',adg.adg_name)) FROM advert_group adg WHERE adg.adg_estate = est.est_id))) FROM estate est LIMIT 1 OFFSET 2
```

`result`
```json
{"id": 3, "name": "Москва, ленинградское шоссе, 72, 50", "advert_groups": ["{\"id\": 25, \"name\": \"avito-4857\"},{\"id\": 26, \"name\": \"avito-6368\"},{\"id\": 27, \"name\": \"avito-5882\"},{\"id\": 28, \"name\": \"avito-6258\"},{\"id\": 29, \"name\": \"avito-1846\"},{\"id\": 30, \"name\": \"avito-8343\"},{\"id\": 31, \"name\": \"avito-2778\"},{\"id\": 32, \"name\": \"avito-2035\"},{\"id\": 33, \"name\": \"avito-2779\"},{\"id\": 34, \"name\": \"avito-6378\"},{\"id\": 35, \"name\": \"avito-1455\"},{\"id\": 36, \"name\": \"avito-8827\"}"]}
```

#### Example 2 (SELECT JSON WITH ManyToOne relation field)
With: 
* Where conditions
* Inner Join
* Group By and Order By
* Offset and Limit
```php
<?php

use \Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use \Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use \Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use \Mash\MysqlJsonSerializer\Wrapper\Mapping;
use \Mash\MysqlJsonSerializer\QueryBuilder\QueryBuilder;

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

$addressField = $builder->addManyToOneField($address, 'address', new FieldStrategy('adv_address'));
$addressField
    ->addSimpleField('adr_id')
    ->addManyToOneField($house, 'house', new FieldStrategy('adr_house'))// will return house field
    ->andWhere('hou.hou_zip > :minZip')
    ->setParameter('minZip', 0)
    ->addSimpleField('hou_id')
    ->addSimpleField('hou_zip');

$sql = $builder->getSql();
```

```sql
SELECT JSON_OBJECT('id',adv.adv_id,'type',adv.adv_type,'address',(SELECT JSON_OBJECT('id',adr.adr_id,'house',(SELECT JSON_OBJECT('id',hou.hou_id,'zip_code',hou.hou_zip) FROM house hou WHERE hou.hou_id = adr.adr_house AND (hou.hou_zip > :minZip) LIMIT 1)) FROM address adr WHERE adr.adr_id = adv.adv_address LIMIT 1)) FROM advert adv INNER JOIN contact ON cnt_type = :type WHERE adv_id >= :minId AND adv_id <= :maxId OR adv_id = :id OR adv_id = :second GROUP BY adv.adv_id ORDER BY adv.adv_id DESC LIMIT 2 OFFSET 2;
```
`result`
```json
[
        {"id": 8, "type": "rent", "address": {"id": 1, "house": {"id": 1, "zip_code": "125565"}}},
        {"id": 7, "type": "rent", "address": {"id": 1, "house": {"id": 1, "zip_code": "125565"}}}
]
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

$reference = new Table('photo_xref', 'xrf');

$photo   = new Table('photo', 'pht', 'pht_id');
$advert  = new Table('advert', 'adv', 'adv_id');
$mapping = new Mapping();
$mapping
    ->addMap($advert, 'adv_id', 'id')
    ->addMap($advert, 'adv_type', 'type')
    ->addMap($photo, 'pht_id', 'id')
    ->addMap($photo, 'pht_hash', 'hash')
;

$builder = new QueryBuilder($advert, new FieldWrapper($mapping));
$builder
    ->select()
    ->addSimpleField('adv_id')
    ->addSimpleField('adv_type')
;

$strategy = new ReferenceStrategy(
    new Reference(
        new Pair($advert, 'adv_id'),
        new Pair($reference, 'xref_adv_id')
    ),
    new Reference(
        new Pair($photo, 'pht_id'),
        new Pair($reference, 'xref_pht_id')
    )
);

$builder
    ->addManyToManyField(
        $photo,
        'photos',
        $strategy
    )
    ->addSimpleField('pht_id')
    ->addSimpleField('pht_hash')
;

$sql = $builder->getSql();
```

```sql
SELECT JSON_OBJECT('id',adv.adv_id,'type',adv.adv_type,'photos',JSON_ARRAY((SELECT GROUP_CONCAT(JSON_OBJECT('id',pht.pht_id,'hash',pht.pht_hash)) FROM photo pht  INNER JOIN photo_xref xrf ON pht.pht_id = xrf.xref_pht_id WHERE adv.adv_id = xrf.xref_adv_id))) FROM advert adv;
```
`result`
```json
[
        {"id": 1, "type": "rent", "photos": ["{\"id\": 3, \"hash\": \"01067dafc86430520591952b95797a05a76cf1e1bdca40cfb50f94a4bf6c75e8\"},{\"id\": 2, \"hash\": \"135ca3b22f3cce8663449dc6412143c8a0ddddf10c39f92d19238b388151073c\"},{\"id\": 5, \"hash\": \"6652a85cd261ca2973530c2b6408b58a7d1c39d319300b3d0832c61a76a0ed17\"},{\"id\": 1, \"hash\": \"d24d67b28e593f445aed636f6bb3739bb67495f5350b387980354ed247a4a3b5\"},{\"id\": 4, \"hash\": \"de7db16b0b32aada4d25a84ef5217934a6ea32055b10fbb34518d38323eb40c4\"}"]},
        {"id": 2, "type": "rent", "photos": ["{\"id\": 3, \"hash\": \"01067dafc86430520591952b95797a05a76cf1e1bdca40cfb50f94a4bf6c75e8\"},{\"id\": 2, \"hash\": \"135ca3b22f3cce8663449dc6412143c8a0ddddf10c39f92d19238b388151073c\"},{\"id\": 5, \"hash\": \"6652a85cd261ca2973530c2b6408b58a7d1c39d319300b3d0832c61a76a0ed17\"},{\"id\": 1, \"hash\": \"d24d67b28e593f445aed636f6bb3739bb67495f5350b387980354ed247a4a3b5\"},{\"id\": 4, \"hash\": \"de7db16b0b32aada4d25a84ef5217934a6ea32055b10fbb34518d38323eb40c4\"}"]}
]
```
