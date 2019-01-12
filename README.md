# mysql-json-serializer
This solution will help you to get serialized data from mysql

## About package
This package allows to get json objects already mapped by created mapping scheme. You will not lost time after query because you will get json from database. 

## Examples
You will be able to make easy difficult query and get already mapped data. For example I will show you some SQL queries which I created by this package.

### Example 1
```php
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
            
        $sql = $builder->getSql();
```

```sql
SELECT JSON_OBJECT('id',est.est_id,'name',est.est_name,'advert_groups',JSON_ARRAY((SELECT GROUP_CONCAT(JSON_OBJECT('id',adg.adg_id,'name',adg.adg_name)) FROM advert_group adg WHERE adg.adg_estate = est.est_id))) FROM estate est LIMIT 1 OFFSET 2
```

`result`
```json
{"id": 3, "name": "Москва, ленинградское шоссе, 72, 50", "advert_groups": ["{\"id\": 25, \"name\": \"avito-4857\"},{\"id\": 26, \"name\": \"avito-6368\"},{\"id\": 27, \"name\": \"avito-5882\"},{\"id\": 28, \"name\": \"avito-6258\"},{\"id\": 29, \"name\": \"avito-1846\"},{\"id\": 30, \"name\": \"avito-8343\"},{\"id\": 31, \"name\": \"avito-2778\"},{\"id\": 32, \"name\": \"avito-2035\"},{\"id\": 33, \"name\": \"avito-2779\"},{\"id\": 34, \"name\": \"avito-6378\"},{\"id\": 35, \"name\": \"avito-1455\"},{\"id\": 36, \"name\": \"avito-8827\"}"]}
```
