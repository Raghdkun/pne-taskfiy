<?php

/**
 * @see       https://github.com/laminas/laminas-stdlib for the canonical source repository
 * @copyright https://github.com/laminas/laminas-stdlib/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-stdlib/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Stdlib\Hydrator;

use Laminas\Stdlib\Exception;
use ReflectionClass;

class Reflection extends AbstractHydrator
{
    /**
     * Simple in-memory array cache of ReflectionProperties used.
     * @var \ReflectionProperty[]
     */
    protected static $reflProperties = [];

    /**
     * Extract values from an object
     *
     * @param  object $object
     * @return array
     */
    public function extract($object)
    {
        $result = [];
        foreach (self::getReflProperties($object) as $property) {
            $propertyName = $this->extractName($property->getName(), $object);
            if (!$this->filterComposite->filter($propertyName)) {
                continue;
            }

            $value = $property->getValue($object);
            $result[$propertyName] = $this->extractValue($propertyName, $value, $object);
        }

        return $result;
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param  array $data
     * @param  object $object
     * @return object
     */
    public function hydrate(array $data, $object)
    {
        $reflProperties = self::getReflProperties($object);
        foreach ($data as $key => $value) {
            $name = $this->hydrateName($key, $data);
            if (isset($reflProperties[$name])) {
                $reflProperties[$name]->setValue($object, $this->hydrateValue($name, $value, $data));
            }
        }
        return $object;
    }

    /**
     * Get a reflection properties from in-memory cache and lazy-load if
     * class has not been loaded.
     *
     * @param  string|object $input
     * @throws Exception\InvalidArgumentException
     * @return \ReflectionProperty[]
     */
    protected static function getReflProperties($input)
    {
        if (is_object($input)) {
            $input = get_class($input);
        } elseif (!is_string($input)) {
            throw new Exception\InvalidArgumentException('Input must be a string or an object.');
        }

        if (isset(static::$reflProperties[$input])) {
            return static::$reflProperties[$input];
        }

        static::$reflProperties[$input] = [];
        $reflClass                      = new ReflectionClass($input);
        $reflProperties                 = $reflClass->getProperties();

        foreach ($reflProperties as $property) {
            $property->setAccessible(true);
            static::$reflProperties[$input][$property->getName()] = $property;
        }

        return static::$reflProperties[$input];
    }
}
