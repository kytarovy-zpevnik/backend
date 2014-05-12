<?php

namespace Markatom\RestApp\Routing;

use Markatom\RestApp\Naming\Naming;
use Nette\Utils\ArrayList;

/**
 * @author	Tomáš Markacz
 */
class Api extends ArrayList
{

    /** @var string */
    private $mask;

    /** @var array */
    private $options;

    /**
     * @param string $mask
     * @param string|array $options
     */
    public function __construct($mask, $options = [])
    {
        if (is_string($options)) {
            $options = ['api' => $options];
        }

        $this->mask    = $mask;
        $this->options = $options;
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

    public function setNaming(Naming $naming)
    {
        /** @var IRoute $route */
        foreach ($this as $route) {
            $route->setNaming($naming);
        }
    }

    /**
     * @param mixed $index
     * @param IRoute $route
     * @throws \InvalidArgumentException
     */
    public function offsetSet($index, $route)
    {
        if (!$route instanceof IRoute) {
            throw self::invalidInstanceException();
        }

        parent::offsetSet($index, $route);
    }

    /**
     * @return \InvalidArgumentException
     */
    private static function invalidInstanceException()
    {
        return new \InvalidArgumentException('Only instance of Markatom\RestApp\Routing\IRoute can be added into api.');
    }

}
