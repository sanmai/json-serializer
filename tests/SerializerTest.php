<?php
/**
 * This code is licensed under the MIT License.
 *
 * Copyright (c) 2020 Alexey Kopytko <alexey@kopytko.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace Tests\JSONSerializer;

use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializerBuilder;
use JSONSerializer\Serializer;
use PHPUnit\Framework\TestCase;
use Tests\JSONSerializer\Fixtures\ItemExample;
use Tests\JSONSerializer\Fixtures\ItemListExample;

/**
 * @covers \JSONSerializer\Serializer
 */
class SerializerTest extends TestCase
{
    private $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer();
    }

    public function test_it_can_handle_simple_object()
    {
        $json = $this->serializer->serialize(new ItemExample());

        $this->assertSame('{"numbers":[]}', $json);

        $example = $this->serializer->deserialize($json, ItemExample::class);

        /** @var $example ItemExample */
        $this->assertNull($example->number);

        $example = $this->serializer->deserialize('{"itemName": "foo", "number": 1, "numbers": [1, 2, 3]}', ItemExample::class);

        /** @var $example ItemExample */
        $this->assertSame('foo', $example->itemName);
        $this->assertSame(1, $example->number);
        $this->assertSame([1, 2, 3], $example->numbers);
    }

    public function test_it_respects_mixed_case_attributes()
    {
        $example = $this->serializer->deserialize('{"itemNaMe": "foo"}', ItemExample::class);

        /** @var $example ItemExample */
        $this->assertNull($example->itemName);
    }

    public function test_it_can_deserialize_list_of_items()
    {
        $itemList = $this->serializer->deserialize('[{"itemName": "foo", "number": 1, "numbers": [1, 2, 3]}, {"itemName": "bar", "number": 2}]', ItemListExample::class);

        $this->assertInstanceOf(ItemListExample::class, $itemList);

        $this->assertCount(2, $itemList->items);

        $this->assertSame('foo', $itemList->items[0]->itemName);
        $this->assertSame(1, $itemList->items[0]->number);
        $this->assertSame([1, 2, 3], $itemList->items[0]->numbers);

        $this->assertSame('bar', $itemList->items[1]->itemName);
        $this->assertSame(2, $itemList->items[1]->number);
    }

    public function test_it_can_take_custom_builder_with_custom_strategy()
    {
        $builder = SerializerBuilder::create();
        $builder->setPropertyNamingStrategy(
            new class() implements PropertyNamingStrategyInterface {
                public function translateName(PropertyMetadata $property): string
                {
                    if ($property->name === ItemExample::ITEM_NAME_PROPERTY) {
                        return 'bar';
                    }

                    return $property->name;
                }
            }
        );

        $serializer = new Serializer($builder);
        $example = $serializer->deserialize('{"bar": "foo"}', ItemExample::class);

        /** @var $example ItemExample */
        $this->assertSame('foo', $example->itemName);
    }

    public function test_it_can_serializer_with_flags()
    {
        $this->serializer = Serializer::withJSONOptions(JSON_PRETTY_PRINT);

        $json = $this->serializer->serialize(new ItemExample([1]));

        $this->assertSame('{
    "numbers": [
        1
    ]
}', $json);

        $example = $this->serializer->deserialize($json, ItemExample::class);

        /** @var $example ItemExample */
        $this->assertSame([1], $example->numbers);
    }
}
