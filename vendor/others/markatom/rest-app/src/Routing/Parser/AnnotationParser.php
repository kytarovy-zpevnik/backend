<?php

namespace Api;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Diagnostics\Debugger;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Utils\ConfigParameters;

/**
 * Gets routes from resource class method annotations.
 * @author	Tomáš Markacz
 */
class AnnotationParser extends Object
{

	/** @var string[] */
	private $dirs;

	/**
	 * @param array $resourcesDirs
	 */
	public function __construct(array $resourcesDirs)
    {
		$this->dirs = $resourcesDirs;
	}

	/**
	 * @throws \LogicException
	 * @return array
	 */
	private function parse()
	{
		$this->loadResourceClasses();

		$routes = [];
		foreach (get_declared_classes() as $class) {
			$class = ClassType::from($class);

            $isResource = array_reduce($this->dirs, function($isResource, $dir) use ($class) {
                return $isResource || Strings::startsWith($class->getFileName(), $dir . '/');
            }, FALSE);

			if ($isResource) {
				$version = $class->getAnnotation('version');
				if ($version === NULL) {
					throw new \LogicException('Please specify api version in class ' . $class->getName() . '.');
				}
				if (!isset($routes[$version])) {
					$routes[$version]['private'] = [];
					$routes[$version]['public']  = [];
				}
				foreach ($class->getMethods() as $method) {
					$annotations = $method->getAnnotations();
					if ($method->isPublic()) {
						$keys = array_intersect(array_keys($annotations), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
						foreach ($keys as $key) {
							foreach ($annotations[$key] as $mask) {
								$mask = preg_replace('|<([0-9a-zA-Z_-]+) ([^>]+)>|', '(\2)', $mask);
								$mask = preg_replace('|<([0-9a-zA-Z_-]+)>|', '([0-9a-zA-Z_-]+)', $mask);
								$routes[$version]['private'][] = $route = new Route($key, $mask, $class->getName(), $method->getName());
								if (isset($annotations['public'])) {
									$routes[$version]['public'][] = $route;
								}
							}
						}
					}
				}
			}
		}
		return $routes;
	}


	/**
	 * Includes resource php files, get_declared_classes() returns resource classes.
	 */
	private function loadResourceClasses()
	{
        foreach ($this->dirs as $dir) {
            foreach (Finder::findFiles('*.php')->from($dir) as $path => $file) {
                require_once $path;
            }
        }
	}

}