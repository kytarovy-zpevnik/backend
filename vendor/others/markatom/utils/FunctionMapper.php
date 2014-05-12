<?php

namespace Utils;

use Nette\Object;

/**
 * Maps function arguments from associative array.
 * @author	Tomáš Markacz
 */
class FunctionMapper extends Object
{

    /**
     * @param callable $callback
     * @return array
     */
    public static function getArguments(callable $callback)
    {
        $reflection = self::getCallableReflection($callback);

        return array_map(function (\ReflectionParameter $arg) {
            $return['name'] = $arg->getName();

            if ($arg->isDefaultValueAvailable()) {
                $return['default'] = $arg->getDefaultValue();
            }

            return $return;
        }, $reflection->getParameters());
    }

    /**
     * @param callable $callback
     * @param array $arguments
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function invoke(callable $callback, array $arguments)
    {
        $reflection = self::getCallableReflection($callback);

        $mapped = [];
        foreach ($reflection->getParameters() as $index => $param) {
            if (!array_key_exists($param->getName(), $arguments) && !$param->isOptional()) {
                throw FunctionMapperException::requiredParameter($param->getName());
            }
            $mapped[$index] = $arguments[$param->getName()];
        }

        return call_user_func_array($callback, $mapped);
	}

    /**
     * @param callable $callback
     * @return \ReflectionFunction|\ReflectionMethod
     */
    private static function getCallableReflection(callable $callback)
    {
        if ($callback instanceof \Closure) {
            return new \ReflectionFunction($callback);

        } else {
            if (is_string($callback)) {
                $callback = explode('::', $callback);
            }
            return new \ReflectionMethod($callback[0], $callback[1]);
        }
    }

}

class FunctionMapperException extends \InvalidArgumentException
{

    public static function requiredParameter($name)
    {
        return new self("Value for required parameter $name is missing.");
    }

}
