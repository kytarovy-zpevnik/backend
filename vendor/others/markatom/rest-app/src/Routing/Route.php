<?php

namespace Markatom\RestApp\Routing;

use Markatom\RestApp\Naming\Naming;
use Nette\Object;

/**
 * Single api route.
 * @author	Tomáš Markacz
 *
 * @property-read array $methods
 * @property-read string $mask
 * @property-read array $options
 * @property-read int|NULL $sinceVersion
 * @property-read int|NULL $untilVersion
 * @property Naming $naming
 */
class Route extends Object implements IRoute
{

	/** @var array */
	private $methods;

    /** @var string */
    private $mask;

    /** @var array */
    private $options;

    /** @var int */
    private $sinceVersion;

    /** @var int */
    private $untilVersion;

    /** @var  Naming */
    private $naming;

    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH  = 'PATCH';

    const PARAMS_FILTER  = '';

    /**
     * @param array|string $methods
     * @param string $mask
     * @param array|string $options
     * @param int $sinceVersion
     * @param int $untilVersion
     */
    public function __construct($methods, $mask, $options = [], $sinceVersion = NULL, $untilVersion = NULL)
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        if (is_string($options)) {
            $options = array_combine(['resource', 'handler'], explode(':', $options, 2));
        }

        $this->methods      = $methods;
        $this->mask         = $mask;
        $this->options      = $options;
        $this->sinceVersion = $sinceVersion;
        $this->untilVersion = $untilVersion;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return int|NULL
     */
    public function getSinceVersion()
    {
        return $this->sinceVersion;
    }

    /**
     * @return int|NULL
     */
    public function getUntilVersion()
    {
        return $this->untilVersion;
    }

    /**
     * @param Naming $naming
     */
    public function setNaming(Naming $naming)
    {
        $this->naming = $naming;
    }

    /**
     * @return Naming
     */
    public function getNaming()
    {
        return $this->naming;
    }

}
