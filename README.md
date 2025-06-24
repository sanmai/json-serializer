# Flexible JSON Serializer

[![Latest Stable Version](https://poser.pugx.org/sanmai/json-serializer/v/stable)](https://packagist.org/packages/sanmai/json-serializer)
[![Coverage Status](https://coveralls.io/repos/github/sanmai/json-serializer/badge.svg?branch=master)](https://coveralls.io/github/sanmai/json-serializer?branch=master)

This library is a thin wrapper around [jms/serializer](https://github.com/schmittjoh/serializer). 

```
composer require sanmai/json-serializer
```

The purpose of this library is to make simpler deserialization/serialization of objects and, specifically, of arrays of objects, and scalar values. All you need is to follow a simple protocol.

### ItemList

JMS Serializer supports deserializing arrays out of the box, but it is ever so slightly complicated since a user must specify a type in a full form, as in `array<T>`, all the while returned deserialized value will be a plain array. This library abstracts away this extra complexity by providing a two-method protocol instead.

For example, deserialising this object:

```php
use JSONSerializer\Contracts\ItemList;

class ItemListExample implements ItemList
{
    /** @var ItemExample[] */
    public $items = [];

    public static function getListType(): string
    {
        return ItemExample::class;
    }

    public static function withList(array $list)
    {
        $itemList = new self();
        $itemList->items = $list;

        return $itemList;
    }
}
```

From a JSON array:
```json
[
    {"name": "foo"},
    {"name": "bar"}
]
```

With an all-familiar method:

```php
use JSONSerializer\Serializer;

$serializer = new Serializer();

$result = $serializer->deserialize($json, ItemListExample::class);
```

Will leave an instance of `ItemListExample` in `$result` with `$result->items` filled with two items as in the source array.

### ScalarValue

There's a similar convenience interface called `ScalarValue` to aid with unserializing wrapped primitive scalar values.

```php
use JSONSerializer\Contracts\ScalarValue;

class ScalarValueExample implements ScalarValue
{
    /** @var int */
    public $value;

    public static function withValue($value)
    {
        $item = new self();
        $item->value = $value;

        return $item;
    }

    public static function getType(): string
    {
        return 'int';
    }
}
```
