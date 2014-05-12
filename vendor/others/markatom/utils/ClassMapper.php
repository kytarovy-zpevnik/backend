<?php

namespace Utils;

use Nette\InvalidArgumentException;
use Nette\Object;

/**
 * Maps replacements to mask string for class name.
 * @author	Tomáš Markacz
 */
class ClassMapper extends Object
{

    /**
     * @param string $mask
     * @param array $replacements
     * @return string
     * @throws \Nette\InvalidArgumentException
     */
    public static function getClass($mask, array $replacements = [])
	{
        $replacements = array_map(function ($replacement) {
            return ucfirst($replacement);
        }, $replacements);

		$class = preg_replace_callback('~\*~', function () use (& $replacements) {
			if (empty($replacements)) {
				throw new InvalidArgumentException('There are less placeholders than replacements.');
			}
			return array_shift($replacements);
		}, $mask);

		if (!empty($replacements)) {
			throw new InvalidArgumentException('There are more placeholders than replacements.');
		}

		return $class;
	}

} 