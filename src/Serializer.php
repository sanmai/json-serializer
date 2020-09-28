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

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use JSONSerializer\Contracts\ItemList;

final class Serializer implements SerializerInterface
{
    private const SERIALIZATION_JSON = 'json';

    private static $addGlobalIgnoredAnnotations = true;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(?string $cacheDirectory = null)
    {
        $builder = SerializerBuilder::create();

        $builder->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(
                new IdenticalPropertyNamingStrategy()
            )
        );

        /**
         * @see https://jmsyst.com/libs/serializer/master/configuration#configuring-a-cache-directory
         */
        if ($cacheDirectory !== null) {
            $builder->setCacheDir($cacheDirectory);
        }

        /** @psalm-suppress MixedAssignment */
        $this->serializer = $builder->build();

        // @codeCoverageIgnoreStart
        if (self::$addGlobalIgnoredAnnotations) {
            // Ignore Phan/Psalm issue-suppressing annotations
            AnnotationReader::addGlobalIgnoredName('phan');
            AnnotationReader::addGlobalIgnoredName('psalm');
            AnnotationReader::addGlobalIgnoredName('template');
            // But do that just once
            self::$addGlobalIgnoredAnnotations = false;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @see \JMS\Serializer\SerializerInterface::serialize()
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param mixed $data
     */
    public function serialize($data, string $format = self::SERIALIZATION_JSON, ?SerializationContext $context = null, ?string $type = null): string
    {
        return $this->serializer->serialize($data, $format, $context, $type);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $type
     * @psalm-return T
     *
     * @see \JMS\Serializer\SerializerInterface::deserialize()
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function deserialize(string $data, string $type, string $format = self::SERIALIZATION_JSON, ?DeserializationContext $context = null)
    {
        if (\is_subclass_of($type, ItemList::class)) {
            return $this->deserializeListType($data, $type, $format, $context);
        }

        return $this->serializer->deserialize($data, $type, $format, $context);
    }

    /**
     * @template T
     *
     * @param class-string<ItemList<T>> $type
     *
     * @return ItemList<T>
     */
    private function deserializeListType(string $data, string $type, string $format = self::SERIALIZATION_JSON, ?DeserializationContext $context = null)
    {
        /** @var string $arrayType */
        $arrayType = \sprintf('array<%s>', $type::getListType());

        $list = $this->serializer->deserialize($data, $arrayType, $format, $context);

        return $type::withList($list);
    }
}
