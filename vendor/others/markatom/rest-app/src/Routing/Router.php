<?php

namespace Markatom\RestApp\Routing;

use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Naming\Naming;
use Nette\Http\IRequest;
use Nette\Utils\ArrayList;
use Nette\Utils\Json;

/**
 * @author	Tomáš Markacz
 */
class Router extends ArrayList implements IRouter
{

    /** @var \Markatom\RestApp\Naming\Naming */
    private $naming;

    /**
     * @param IRequest $httpRequest
     * @throws NoRouteException
     * @throws MultipleMatchingRoutesException
     * @return \Markatom\RestApp\Api\Request
     */
	public function getApiRequest(IRequest $httpRequest)
	{
		$apiRequest = FALSE;
        /** @var Api $api */
		foreach ($this as $api) {
            if (UrlParser::prefixMatch($httpRequest->getUrl(), $api->getMask())) {
                $api->setNaming($this->naming);

                /** @var Route $route */
                foreach ($api as $route) {
                    if (!in_array($httpRequest->getMethod(), $route->getMethods())) {
                        continue;
                    }

                    $mask    = $api->getMask() . '/' . $route->getMask();
                    $options = array_merge($api->getOptions(), $route->getOptions(), ['method' => $httpRequest->getMethod()]);
                    $params  = UrlParser::parse($httpRequest->getUrl(), $mask, $options);

                    if ($params === FALSE) {
                        continue;
                    }

                    if (isset($params['version'])) {
                        if ($route->getSinceVersion() !== NULL && $route->getSinceVersion() > $params['version']) {
                            continue;
                        }
                        if ($route->getUntilVersion() !== NULL && $route->getUntilVersion() < $params['version']) {
                            continue;
                        }
                    }

                    if ($apiRequest) {
                        throw self::multipleMatchingRoutesException($httpRequest, $apiRequest, $this->createApiRequest($httpRequest, $params));
                    }

                    $apiRequest = $this->createApiRequest($httpRequest, $params);
                }
			}
		}

        if (!$apiRequest) {
            throw self::noRouteException($httpRequest);
        }

        return $apiRequest;
    }

    /**
     * @param mixed $index
     * @param Api $route
     * @throws \InvalidArgumentException
     */
    public function offsetSet($index, $route)
    {
        if (!$route instanceof Api) {
            throw self::invalidInstanceException();
        }

        parent::offsetSet($index, $route);
    }

    /**
     * @param IRequest $httpRequest
     * @param array $params
     * @throws CannotCreateApiRequestException
     * @return Request
     */
    private function createApiRequest(IRequest $httpRequest, array $params)
    {
        if (!isset($params['api']) || !isset($params['resource']) || !isset($params['handler'])) {
            throw self::cannotCreateApiRequestException();
        }

		if (!isset($params['version'])) {
			$params['version'] = NULL;
		}

		$contentType = $httpRequest->getHeader('Content-Type', '');

		$delimiter = strpos($contentType, ';');
		$mimeType  = trim(substr($contentType, 0, $delimiter === FALSE ? NULL : $delimiter));

		switch ($mimeType) {
			case 'application/json':
				$post = Json::decode(file_get_contents('php://input'), Json::FORCE_ARRAY);
				break;

			case 'application/x-www-form-urlencoded':
				$post = $httpRequest->getPost();
				break;

			default: // raw
				$post = file_get_contents('php://input');
		}

		$apiName      = $params['api'];
		$apiVersion   = $params['version'];
		$resourceName = $params['resource'];
		$handlerName  = $params['handler'];

		unset($params['api'], $params['version'], $params['resource'], $params['handler']);

        return new Request($apiName, $apiVersion, $resourceName, $handlerName, $httpRequest->getMethod(), $httpRequest->getHeaders(), $params, $httpRequest->getQuery(), $post, $httpRequest->getFiles());
    }

    /**
     * @return CannotCreateApiRequestException
     */
    private static function cannotCreateApiRequestException()
    {
        return new CannotCreateApiRequestException('Cannot create api request, some of required params (api, resource, handler) are missing.');
    }

    /**
     * @param IRequest $httpRequest
     * @param Request $a
     * @param Request $b
     * @return MultipleMatchingRoutesException
     */
    private static function multipleMatchingRoutesException(IRequest $httpRequest, Request $a, Request $b)
    {
        return new MultipleMatchingRoutesException(
            'Multiple matching routes for request '
            . $httpRequest->getMethod() . ' ' . $httpRequest->getUrl()->getRelativeUrl()
            . ' (' . $a->getName() . ' vs. ' . $b->getName() . ').'
        );
    }

    /**
     * @param IRequest $httpRequest
     * @return NoRouteException
     */
    private static function noRouteException(IRequest $httpRequest)
    {
        return new NoRouteException(
            'No matching route for request '
            . $httpRequest->getMethod() . ' ' . $httpRequest->getUrl()->getRelativeUrl() . '.'
        );
    }

    /**
     * @return \InvalidArgumentException
     */
    private static function invalidInstanceException()
    {
        return new \InvalidArgumentException('Only instance of Markatom\RestApp\Routing\Api can be added into router.');
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

class RouterException extends \LogicException { }

class MethodNotAllowedException extends RouterException { }

class CannotCreateApiRequestException extends RouterException { }

class MultipleMatchingRoutesException extends RouterException { }

class NoRouteException extends RouterException { }

class NoHandlerException extends RouterException { }

class VersionRequiredException extends RouterException { }

class AccessException extends \RuntimeException { }

class AuthenticationException extends AccessException { }

class AuthorizationException extends AccessException { }