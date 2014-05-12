<?php

namespace Markatom\RestApp\Routing;

/**
 * Router factory interface.
 * @author	Tomáš Markacz
 */
interface IRouterFactory
{

    /** @return Router */
	public function create();

}