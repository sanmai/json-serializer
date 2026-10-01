# Flexible JSON Serializer

[![Latest Stable Version](https://poser.pugx.org/sanmai/json-serializer/v/stable)](https://packagist.org/packages/sanmai/json-serializer)
[![Coverage Status](https://coveralls.io/repos/github/sanmai/json-serializer/badge.svg?branch=main)](https://coveralls.io/github/sanmai/json-serializer?branch=main)

This library is a thin wrapper around [jms/serializer](https://github.com/schmittjoh/serializer).

```
composer require sanmai/json-serializer
```

This library makes it simpler to serialize and deserialize arrays of objects, scalar values, and plain objects. All you need is to follow a simple protocol.

## How it works

The library provides one general rule for deserializing every JSON value into a domain type.

Normally, application code using JMS Serializer follows a simple and predictable pattern:

```php
$result = $serializer->deserialize($json, MyType::class, 'json');
```

This works well for objects and generalizes naturally to different domain types. However, the pattern breaks for root-level arrays and scalar values. The caller must use JMS type expressions and primitive type strings instead of a class. For example, deserializing this array:

```json
[
    {"name": "foo"},
    {"name": "bar"}
]
```

Requires an out-of-pattern string type:

```php
$items = $serializer->deserialize($json, sprintf('array<%s>', ItemExample::class), 'json');
```

A scalar value requires a similar exception:

```php
$number = $serializer->deserialize($json, 'int', 'json');
```

Consequently, the caller has to use a different kind of type declaration to handle a plain array or primitive value instead of a domain object.

This library restores the general class-based rule for all four JSON shapes:

```php
$item = $serializer->deserialize($json, ItemExample::class);
$items = $serializer->deserialize($json, ItemListExample::class);
$map = $serializer->deserialize($json, ItemMapExample::class);
$number = $serializer->deserialize($json, ScalarValueExample::class);
```

A class like `ItemExample` already declares its own fields and its own serialization guidance. Arrays and scalar values have no such place, so that knowledge leaks into the calling code, or into a serializer adapter class.

The interfaces this library adds put that knowledge in the type itself, turning lists and scalars into first-class domain members. So there is no extra class and no adapter layer, and nothing about a type lives outside of it. Calling code can then ask for a domain type whatever the shape of the JSON, and the library handles the rest.

## ItemList

JMS Serializer supports deserializing arrays out of the box, but it is ever so slightly complicated since a user must specify a type in a full form, as in `array<T>`, all the while returned deserialized value will be a plain untyped array.

This library abstracts away this extra complexity by providing a two-method protocol instead to constructs the domain collection.

Here is how it looks:

```php
use JSONSerializer\Contracts\ItemList;

class ItemListExample implements ItemList
{
    /** @var ItemExample[] */
    public array $items = [];

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

This leaves an instance of `ItemListExample` in `$result`, with `$result->items` holding the two items from the source array.

## ItemMap

`ItemList` covers a root-level JSON array, but some APIs return a root-level JSON object with keys that are data, not field names:

```json
{
    "is_urgent": {"no": 0.1, "yes": 0.9},
    "department": {"billing": 0.8, "technical": 0.2}
}
```

The `ItemMap` interface declares the type of the keys and the type of the values. The value type can be a class name or a JMS type expression, so a map of plain values needs no extra class.

```php
use JSONSerializer\Contracts\ItemMap;

class ItemMapExample implements ItemMap
{
    /** @var array<string, array<string, float>> */
    public array $items = [];

    public static function getKeyType(): string
    {
        return 'string';
    }

    public static function getItemType(): string
    {
        return 'array<string, float>';
    }

    public static function withMap(array $map)
    {
        $distributions = new self();
        $distributions->items = $map;

        return $distributions;
    }
}
```

```php
$distributions = $serializer->deserialize($json, Distributions::class);
$distributions->items['is_urgent']['yes']; // 0.9
```

The keys of the JSON object stay as they are. An empty JSON object `{}` gives an empty map. Do not implement both `ItemList` and `ItemMap` in one class: `ItemList` takes precedence.

## ScalarValue

There is a similar convenience interface called `ScalarValue` to aid with deserializing wrapped primitive scalar values.

```php
use JSONSerializer\Contracts\ScalarValue;

class ScalarValueExample implements ScalarValue
{
    public int $value;

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

## Troubleshooting

### Class "JSONSerializer\Serializer" not found

If you are getting an error that class `JSONSerializer\Serializer` was not found, you must have forgotten to do:

```
composer require sanmai/json-serializer
```
