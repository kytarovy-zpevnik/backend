<?php

namespace Markatom\RestApp\Routing;

use Markatom\RestApp\Naming\Naming;
use Nette\Object;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class CrudRoute extends Object implements IRoute
{

    /** @var \Markatom\RestApp\Routing\Route */
    private $route;

    private static $methods = [
        Route::METHOD_POST   => 0,
        Route::METHOD_GET    => 1,
        Route::METHOD_PUT    => 2,
        Route::METHOD_DELETE => 3
    ];

    const WITH_ID    = TRUE;
    const WITHOUT_ID = FALSE;

    private static $handlers = [
        self::WITH_ID    => [NULL,     'read',    'update',    'delete'],
        self::WITHOUT_ID => ['create', 'readAll', 'updateAll', 'deleteAll']
    ];

	public function __construct($resource = NULL, $options = [], $sinceVersion = NULL, $untilVersion = NULL)
    {
        $methods = [Route::METHOD_POST, Route::METHOD_GET, Route::METHOD_PUT, Route::METHOD_DELETE];

        $resourceMask = $resource === NULL
            ? '<resource>'
            : $resource;

        $options = array_merge([
                Route::PARAMS_FILTER => function (array $params) {
                        $params['handler'] = self::getHandler($params);
                        return $params;
                    },
                'resource' => $resource
            ], $options);

        $this->route = new Route($methods, $resourceMask . '[/<id>[/<relation>[/<relationId>]]]', $options, $sinceVersion, $untilVersion);
    }

    /**
     * @param array $params
     * @throws MethodNotAllowedException
     * @return string
     */
    public function getHandler(array $params)
    {
        $withId = isset($params['relation'])
            ? isset($params['relationId'])
            : isset($params['id']);

        $handler = self::$handlers[$withId][self::$methods[$params['method']]];

        if ($handler === NULL) {
            throw self::methodNotAllowedException($params['method']);
        }

        if (isset($params['relation'])) {
            $words = $this->route->naming->convention->parse($params['relation']);
            if (isset($params['relationId'])) {
                array_push($words, $this->route->naming->inflector->singularize(array_pop($words))); // assumes last word is noun
            }

            return $handler . $this->route->naming->pascalize($words);
        } else {
            return $handler;
        }
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->route->getMethods();
    }

    /**
     * @return string
     */
    public function getMask()
    {
        return $this->route->getMask();
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->route->getOptions();
    }

    /**
     * @return int|NULL
     */
    public function getSinceVersion()
    {
        return $this->route->getSinceVersion();
    }

    /**
     * @return int|NULL
     */
    public function getUntilVersion()
    {
        return $this->route->getUntilVersion();
    }

    /**
     * @param Naming $naming
     */
    public function setNaming(Naming $naming)
    {
        $this->route->naming = $naming;
    }

    /**
     * @return Naming
     */
    public function getNaming()
    {
        $this->route->naming;
    }

    /**
     * @return MethodNotAllowedException
     */
    private static function methodNotAllowedException()
    {
        return new MethodNotAllowedException("Method POST with id param not allowed on CRUD resource");
    }

}
