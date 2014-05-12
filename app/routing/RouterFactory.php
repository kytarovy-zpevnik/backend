<?php

namespace Routing;

use Markatom\RestApp\Routing\Api;
use Markatom\RestApp\Routing\CrudRoute;
use Markatom\RestApp\Routing\IRouter;
use Markatom\RestApp\Routing\Router;
use Nette\Object;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class RouterFactory extends Object
{

    /**
     * @return IRouter
     */
    public static function create()
    {
        $router = new Router();

        $router[] = $api = new Api('api/frontend/version/<version>', 'frontend');

        $api[] = $route = new CrudRoute('users');

        return $router;
    }

} 