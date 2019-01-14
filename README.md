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

$sql = $builder->getSql();
```

```sql
SELECT JSON_OBJECT('id',est.est_id,'name',est.est_name,'advert_groups',(SELECT (CASE WHEN adg.adg_estate IS NULL THEN NULL ELSE CAST(CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id',adg.adg_id,'name',adg.adg_name)), ']') AS JSON) END) FROM advert_group adg INNER JOIN estate est ON est.est_id = adg.adg_estate GROUP BY est.est_id LIMIT 1)) FROM estate est LIMIT 1 OFFSET 2;
```

`result`
```json
{"id": 3, "name": "Москва, окская улица, 3к1", "advert_groups": [{"id": 12, "name": "avito-1593947950"}]}
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

$builder = new QueryBuilder($advert, new FieldWrapper($mapping));
$builder
    ->innerJoin($contact, 'cnt.cnt_type = :type AND adv.adv_contact = cnt.cnt_id')
    ->setParameter('type', 'owner')
    ->setOffset(2)
    ->setLimit(2);

$sql = $builder->getSql();
```

```sql
SELECT JSON_OBJECT('id',adv.adv_id,'type',adv.adv_type,'address',(SELECT JSON_OBJECT('id',adr.adr_id,'house',(SELECT JSON_OBJECT('id',hou.hou_id,'zip_code',hou.hou_zip) FROM house hou WHERE hou.hou_id = adr.adr_house LIMIT 1)) FROM address adr WHERE adr.adr_id = adv.adv_address LIMIT 1)) FROM advert adv INNER JOIN contact cnt ON cnt.cnt_type = :type AND adv.adv_contact = cnt.cnt_id LIMIT 2 OFFSET 2;
```
`result`
```json
[
        {"id": 26, "type": "rent", "address": {"id": 1, "house": {"id": 1, "zip_code": "125565"}}},
        {"id": 37, "type": "rent", "address": {"id": 2, "house": {"id": 2, "zip_code": "125565"}}}
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

$photo
    ->addSimpleField('pht_id')
    ->addSimpleField('pht_hash')
;

$advert->addManyToManyField($photo, 'photos', $strategy);

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
