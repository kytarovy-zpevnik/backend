<?php

namespace Routing;

use Markatom\RestApp\Routing\Api;
use Markatom\RestApp\Routing\CrudRoute;
use Markatom\RestApp\Routing\IRouter;
use Markatom\RestApp\Routing\Route;
use Markatom\RestApp\Routing\Router;
use Nette\Object;

/**
 * Configures api routes.
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

        $router[] = $api = new Api('frontend/<version>', 'frontend');

		// generic create-read-update-delete routes for users resource
        $api[] = new CrudRoute('users');
        $api[] = new CrudRoute('songs');
        $api[] = new CrudRoute('songbooks');
        $api[] = new CrudRoute('passwordreset');


        // custom routes for sessions resource
		$api[] = new Route([Route::METHOD_POST], 'sessions', 'sessions:create');
		$api[] = new Route([Route::METHOD_GET], 'sessions/active', 'sessions:readActive');
		$api[] = new Route([Route::METHOD_DELETE], 'sessions/active', 'sessions:deleteActive');

        return $router;
    }

}
