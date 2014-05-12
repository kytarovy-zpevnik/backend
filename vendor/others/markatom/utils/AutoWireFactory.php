<?php

namespace Utils;

use Nette\DI\Container;
use Nette\Object;

/**
 * Factory for creating objects with constructor dependencies.
 * @author	Tomáš Markacz
 */
class AutoWireFactory extends Object
{

	/** @var \Nette\DI\Container */
	private $container;

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param string $class
	 * @return object
	 */
	public function create($class)
	{
		return $this->container->createInstance($class);
	}

} 