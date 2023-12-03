<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Class TypedCollection
 *
 * @package Previon\Base\Collections
 */
class TypedCollection extends Collection
{
    /**
     * An array of allowed object types
     *
     * @var array
     */
    protected static $allowedTypes = [];

    /**
     * TypedCollection constructor.
     *
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->assertValidTypes($this->getArrayableItems($items));
        parent::__construct($items);
    }

    /**
     * @param mixed $value
     * @param null  $key
     *
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $this->assertValidType($value);

        return parent::prepend($value, $key);
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function push(...$value)
    {
        $this->assertValidType($value);

        return parent::push($value);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function put($key, $value)
    {
        $this->assertValidType($value);

        return parent::put($key, $value);
    }

    /**
     * Returns an untyped collection with all items
     */
    public function untype()
    {
        return Collection::make($this->items);
    }

    /**
     * @param $item
     */
    protected function assertValidType($item)
    {
        $result = array_reduce(static::$allowedTypes,
            function ($isValid, $allowedType) use ($item) {
                return $isValid ?: $item instanceof $allowedType;
            }, false);

        if (!$result) {
            throw new InvalidArgumentException(sprintf(
                'Collection of type %s only accepts objects of the following type(s): %s. Type %s given.',
                get_class($this), implode(', ', static::$allowedTypes), $item
            ));
        }
    }

    /**
     * @param array $items
     */
    protected function assertValidTypes(array $items)
    {
        array_map(function ($item) {
            $this->assertValidType($item);
        }, $items);
    }
}
