<?php

namespace Markatom\RestApp;

use ErrorException;
use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Naming\Naming;
use Markatom\RestApp\Resource\IResource;
use Markatom\RestApp\Resource\IResourceFactory;
use Markatom\RestApp\Routing\IRouter;
use Markatom\RestApp\Routing\MethodNotAllowedException;
use Markatom\RestApp\Routing\NoHandlerException;
use Markatom\RestApp\Routing\NoRouteException;
use Markatom\RestApp\Routing\Router;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Object;
use Tracy\Debugger;

/**
 * Rest application.
 * @author	Tomáš Markacz
 */
class Application extends Object
{

    /** @var int */
    public static $maxForwardingCount = 20;

    /** @var callable[] @desc function(Application $sender) */
    public $onStartup;

    /** @var callable[] @desc function(Application $sender, Request $request) */
    public $onRequest;

    /** @var callable[] @desc function(Application $sender, Resource $resource) */
    public $onResource;

    /** @var callable[] @desc function(Application $sender, IResponse $response) */
    public $onResponse;

    /** @var callable[] @desc function(Application $sender, \Exception $e) */
    public $onError;

    /** @var callable[] @desc function(Application $sender, \Exception $e = NULL) */
    public $onShutdown;

    /** @var IRequest */
    private $httpRequest;

    /** @var IResponse */
    private $httpResponse;

    /** @var Router */
    private $router;

    /** @var IResourceFactory */
    private $resourceFactory;

    /** @var \Nette\DI\Container */
    private $dic;

    /** @var Request[] */
    private $requestStack = [];

    /** @var IResource */
    private $resource;

    /** @var bool */
    private $responseSent = FALSE;

    /**
     * @param IRequest $httpRequest
     * @param IResponse $httpResponse
     * @param IRouter $router
     * @param IResourceFactory $resourceFactory
     * @param Naming $naming
     * @param Container $dic
     */
    public function __construct(IRequest $httpRequest, IResponse $httpResponse, IRouter $router, IResourceFactory $resourceFactory, Naming $naming, Container $dic)
    {
        $router->setNaming($naming);

        $this->httpRequest     = $httpRequest;
        $this->httpResponse    = $httpResponse;
        $this->router          = $router;
        $this->resourceFactory = $resourceFactory;
        $this->dic             = $dic;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        try {
            $this->onStartup($this);

            $request = $this->router->getApiRequest($this->httpRequest);
            $this->dic->addService('apiRequest', $request);

            $this->processRequest($request);

            $this->onShutdown($this);

        } catch (\Exception $e) {
            $this->onError($this, $e);
            $this->onShutdown($this, $e);
        }
    }

    /**
     * @param Response $response
     * @throws ApplicationException
     */
    public function sendResponse(Response $response)
    {
        if ($this->responseSent) {
            throw ApplicationException::responseAlreadySent();
        }

        $this->onResponse($this, $response);
        $response->send($this->httpRequest, $this->httpResponse);

        $this->responseSent = TRUE;
    }

    /**
     * Returns all requests.
     * @return Request[]
     */
    public function getRequestStack()
    {
        return $this->requestStack;
    }

    /**
     * Returns current resource.
     * @return IResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param Request $request
     * @throws ApplicationException
     */
    private function processRequest(Request $request)
    {
        if (count($this->requestStack) > self::$maxForwardingCount) {
            throw ApplicationException::tooManyForwardings();
        }

        array_push($this->requestStack, $request);
        $this->onRequest($this, $request);

        $this->resource = $this->createResource($request);
        $this->onResource($this, $this->resource);

        $response = $this->resource->handle($request);

        if ($response instanceof Request) {
            $this->processRequest($response);

        } else {
            $this->sendResponse($response ?: Response::blank());
        }
    }

    /**
     * @param Request $request
     * @return IResource
     */
    private function createResource(Request $request)
    {
        return $this->resourceFactory->create($request->getApiName(), $request->getResourceName(), $request->getApiVersion());
    }

}

class ApplicationException extends \RuntimeException
{

    /**
     * @return ApplicationException
     */
    public static function tooManyForwardings()
    {
        return new self('Too many forwardings in application run.');
    }

    /**
     * @return ApplicationException
     */
    public static function responseAlreadySent()
    {
        return new self('Cannot send response, response already sent.');
    }

}
