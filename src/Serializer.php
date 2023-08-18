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

namespace JSONSerializer;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\Visitor\Factory\JsonDeserializationVisitorFactory;
use JMS\Serializer\Visitor\Factory\JsonSerializationVisitorFactory;
use JSONSerializer\Contracts\ItemList;
use JSONSerializer\Contracts\ScalarValue;
use function is_subclass_of;
use function sprintf;

final class Serializer implements SerializerInterface
{
    private const SERIALIZATION_JSON = 'json';

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerBuilder $builder = null)
    {
        $builder ??= self::makeSerializerBuilder();

        $this->serializer = $builder->build();
    }

    /**
     * Makes a serializer with a set of custom JSON flags, purely out of convenience.
     *
     * @param int $options Bitmask consisting of serialization-related JSON_* options like JSON_PRETTY_PRINT. Defaults to JSON_PRESERVE_ZERO_FRACTION.
     */
    public static function withJSONOptions(int $options = JSON_PRESERVE_ZERO_FRACTION): self
    {
        $visitorFactory = new JsonSerializationVisitorFactory();
        $visitorFactory->setOptions($options);

        $builder = self::makeSerializerBuilder();
        $builder->setSerializationVisitor(self::SERIALIZATION_JSON, $visitorFactory);
        $builder->setDeserializationVisitor(self::SERIALIZATION_JSON, new JsonDeserializationVisitorFactory());

        return new self($builder);
    }

    private static function makeSerializerBuilder(): SerializerBuilder
    {
        $builder = SerializerBuilder::create();

        // Required for properties cased as fooBarBaz.
        $builder->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(
                new IdenticalPropertyNamingStrategy()
            )
        );

        return $builder;
    }

    /**
     * @see \JMS\Serializer\SerializerInterface::serialize()
     *
     * @param mixed $data
     */
    public function serialize($data, string $format = self::SERIALIZATION_JSON, SerializationContext $context = null, string $type = null): string
    {
        return $this->serializer->serialize($data, $format, $context, $type);
    }

    /**
     * @psalm-template T
     *
     * @psalm-param class-string<T>|class-string<ItemList>|class-string<ScalarValue> $type
     *
     * @psalm-return T|ItemList|ScalarValue
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @see \JMS\Serializer\SerializerInterface::deserialize()
     */
    public function deserialize(string $data, string $type, string $format = self::SERIALIZATION_JSON, DeserializationContext $context = null)
    {
        if (is_subclass_of($type, ItemList::class)) {
            return $this->deserializeListType($data, $type, $format, $context);
        }

        if (is_subclass_of($type, ScalarValue::class)) {
            return $this->deserializeScalarValue($data, $type, $format, $context);
        }

        return $this->serializer->deserialize($data, $type, $format, $context);
    }

    /**
     * @param class-string<ScalarValue> $type
     *
     * @return ScalarValue
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function deserializeScalarValue(string $data, string $type, string $format = self::SERIALIZATION_JSON, DeserializationContext $context = null)
    {
        $value = $this->serializer->deserialize($data, $type::getType(), $format, $context);

        return $type::withValue($value);
    }

    /**
     * @param class-string<ItemList> $type
     *
     * @return ItemList
     */
    private function deserializeListType(string $data, string $type, string $format = self::SERIALIZATION_JSON, DeserializationContext $context = null)
    {
        /** @var class-string $arrayType */
        $arrayType = sprintf('array<%s>', $type::getListType());

        /** @var array<object> $list */
        $list = $this->serializer->deserialize($data, $arrayType, $format, $context);

        return $type::withList($list);
    }
}
