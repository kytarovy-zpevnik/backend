<?php

namespace Markatom\RestApp\Resource;

use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Api\Response;
use Nette\Object;
use Utils\FunctionMapper;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class Resource extends Object implements IResource
{

	/** @var Request */
	protected $request;

    /**
     * @param Request $request
     * @throws \InvalidArgumentException
     * @return Response
     */
    public function handle(Request $request)
    {
		$this->request = $request;

        if (!method_exists($this, $request->getHandlerName())) {
            throw self::invalidHandler($request->getHandlerName(), get_class($this));
        }

        return FunctionMapper::invoke([$this, $request->getHandlerName()], $request->getParams());
    }

    /**
     * @param string $handler
     * @param string $resourceClass
     * @return \InvalidArgumentException
     */
    private static function invalidHandler($handler, $resourceClass)
    {
        return new \InvalidArgumentException("Given handler $handler not defined in resource class $resourceClass.");
    }

}