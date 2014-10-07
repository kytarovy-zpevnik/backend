<?php

namespace Markatom\RestApp;

use ErrorException;
use Exception;
use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Routing\MethodNotAllowedException;
use Markatom\RestApp\Routing\NoHandlerException;
use Markatom\RestApp\Routing\NoRouteException;
use Nette\Object;
use Nette\Reflection\ClassType;
use Tracy\Debugger;
use Tracy\Helpers;

/**
 * @todo Fill desc.
 * @author Tomáš Markacz
 */
class ErrorHandlers extends Object
{

	private function __construct() { } // static class

	/**
	 * @param Application $application
	 */
	public static function register(Application $application)
	{
		$application->onError[] = [__CLASS__, 'exceptionHandler'];

		Debugger::$onFatalError = [function (Exception $e) use ($application) {
			ob_clean(); // clean output buffer

			if (!headers_sent()) { // if no output sent (all output was in buffer)
				self::exceptionHandler($application, $e); // send exception

			} else {
				Debugger::log($e, Debugger::EXCEPTION); // log exception at least
			}
		}];
	}

	/**
	 * @param Application $application
	 * @param Exception $e
	 */
	public static function exceptionHandler(Application $application, Exception $e)
	{
		$stored = Debugger::log($e, Debugger::EXCEPTION);

		$response = Debugger::$productionMode
			? new Response() // empty response
			: Response::data([
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
				'stored'  => $stored
			]);

		if ($e instanceof NoRouteException || $e instanceof NoHandlerException) {
			$response->setHttpStatus(Response::HTTP_NOT_FOUND);

		} elseif ($e instanceof MethodNotAllowedException) {
			$response->setHttpStatus(Response::HTTP_METHOD_NOT_ALLOWED);

		} else {
			$response->setHttpStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		$application->sendResponse($response);
    }

}
