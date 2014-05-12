<?php

namespace Utils;

use Nette\InvalidArgumentException;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Reflection\Property;
use Nette\StaticClassException;

/**
 * Helper class for filling object's private properties using reflection.
 * @author Tomáš Markacz
 */
class PropertySetter extends Object
{

    /**
     * Fills objects properties with given data.
     * @param \Nette\Object $object Object to be filled.
     * @param array $values Data to be filled into properties in format [propertyName => value].
     * @return array Original values.
     */
	public static function set(Object $object, array $values)
	{
		$properties = self::getProperties($object->getReflection());

        $properties = array_combine(array_map(function (Property $property) {
            return $property->name;
        }, $properties), $properties);

        $original = [];
		foreach ($values as $name => $value) {
			if (!isset($properties[$name])) {
                throw new \InvalidArgumentException('Cannot set an undeclared property ' . get_class($object) . '::$' . $name . '.');
            }

            $properties[$name]->setAccessible(TRUE);
            $original[$name] = $properties[$name]->getValue($object);
            $properties[$name]->setValue($object, $values[$name]);
            $properties[$name]->setAccessible(FALSE);
		}

        return $original;
	}

	/**
	 * Gets all class properties (including parent properties)
	 * @param ClassType $classType
	 * @return array
	 */
	private static function getProperties(ClassType $classType)
	{
        return $classType->properties;

//		$parentClass = $classType->getParentClass();
//		if ($parentClass === NULL) {
//			return $classType->properties;
//		} else {
//			return array_merge($classType->properties, self::getProperties($parentClass));
//		}
	}

}
