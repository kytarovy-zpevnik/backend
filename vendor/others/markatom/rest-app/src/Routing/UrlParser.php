<?php

namespace Markatom\RestApp\Routing;

use Nette\Http\UrlScript;
use Nette\InvalidStateException;
use Nette\Object;

/**
 * @author	Tomáš Markacz
 */
class UrlParser extends Object
{

    /** @link UrlParser::__construct */
    const MASK         = 'mask';
    const VALUE        = 'value';
    const FILTER       = 'filter';
    const FILTER_TABLE = 'filterTable';

    /** @var array */
    private static $paramDefaults = [
        self::MASK  => '[a-zA-Z0-9_.-]+',
        self::VALUE => NULL
    ];

    /**
     * @param UrlScript $url
     * @param string $urlMask
     * @return FALSE
     */
    public static function prefixMatch(UrlScript $url, $urlMask)
    {
        $regex = self::getUrlRegex($urlMask);

        return (bool) preg_match("~^$regex~", $url->getRelativeUrl());
    }

    /**
     * @param UrlScript $url
     * @param string $urlMask
     * @param array $options
     * @return array|FALSE
     */
    public static function parse(UrlScript $url, $urlMask, array $options = [])
    {
        $regex = self::getUrlRegex($urlMask, $options);

		$relativeUrl = $url->getRelativeUrl();
		if ($queryOffset = strpos($relativeUrl, '?')) {
			$relativeUrl = substr($relativeUrl, 0, $queryOffset);
		}

        if (preg_match("~^$regex$~", $relativeUrl, $matches)) {
            foreach ($matches as $index => $match) {
                if (is_string($index)) {
                    $options[$index][self::VALUE] = $match;
                }
            }

            foreach ($options as $name => $param) {
                if ($name !== Route::PARAMS_FILTER) {
                    $value = $param[self::VALUE];

                    if (isset($param[self::FILTER])) {
                        $value = call_user_func($param[self::FILTER], $value);
                    }

                    if (isset($param[self::FILTER_TABLE]) && array_key_exists($value, $param[self::FILTER_TABLE])) {
                        $value = $param[self::FILTER_TABLE][$value];
                    }

                    $params[$name] = $value;
                }
            }

            if (isset($options[Route::PARAMS_FILTER])) {
                $params = call_user_func($options[Route::PARAMS_FILTER], $params);
                unset($options[Route::PARAMS_FILTER]);
            }

            return $params;

        } else {
            return FALSE;
        }
    }

    /**
     * @param string $urlMask
     * @param array $options
     * @return string
     */
    private static function getUrlRegex($urlMask, & $options = [])
    {
        $before = '(?<before>([^<>]*<[^<>]*>)*[^\\\\<>]*)';
        $in     = '(?<optional>([^<>\[\]]*<[^<>]*>)*[^<>\[\]\\\\]*)';

        do {
            $matched = FALSE;
            $urlMask = preg_replace_callback("~$before\\[$in\\]~", function (array $matches) use (& $matched) {
                $matched = TRUE;
                return $matches['before'] . '(' . $matches['optional'] . ')?';
            }, $urlMask);
        } while ($matched);

        $identifier = '(?<identifier>[a-zA-Z_][a-zA-Z0-9_]*)';
        $value      = '(?<value>[^> ]+)';
        $mask       = '(?<mask>[^>]+)';

        $parsedOptions = [];
        $regex = preg_replace_callback("~<$identifier(=$value)?( $mask)?>~", function (array $matches) {
            $identifier = $matches['identifier'];

            if (isset($parsedOptions[$identifier])) {
                throw self::duplicatedParamIdentifierException($identifier);
            }

            $parsedOptions[$identifier] = self::$paramDefaults;

            if (isset($matches['value']) && $matches['value'] !== '') {
                $parsedOptions[$identifier][self::VALUE] = $matches['value'];
            }

            if (isset($matches['mask']) && $matches['mask'] !== '') {
                $parsedOptions[$identifier][self::MASK] = $matches['mask'];
            }

            return "(?<$identifier>{$parsedOptions[$identifier][self::MASK]})";
        }, $urlMask);

        if (is_array($options)) {
            $options = array_map(function ($item) {
                return is_string($item)
                    ? [self::VALUE => $item]
                    : $item;
            }, $options);

            $options = array_merge($parsedOptions, $options);
        }

        return $regex;
    }

    /**
     * @param string $identifier
     * @return InvalidStateException
     */
    private static function duplicatedParamIdentifierException($identifier)
    {
        return new InvalidStateException("Duplicated parameter identifier '$identifier'.");
    }

}
