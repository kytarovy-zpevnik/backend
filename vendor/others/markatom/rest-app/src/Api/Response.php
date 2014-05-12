<?php

namespace Markatom\RestApp\Api;

use Nette;
use Nette\Application\IResponse;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Object;

/**
 * Api resource response.
 * @author Tomáš Markacz
 */
class Response extends Object implements IResponse
{

	/** @var array */
	private $data;

	/** @var array */
	private $headers = [];

	/** @var int */
	private $code;

	const HTTP_OK = 200;
	const HTTP_CREATED = 201;
	const HTTP_NO_CONTENT = 204;

	const HTTP_BAD_REQUEST = 400;
	const HTTP_UNAUTHORIZED = 401;
	const HTTP_FORBIDDEN = 403;
	const HTTP_NOT_FOUND = 404;
	const HTTP_METHOD_NOT_ALLOWED = 405;
	const HTTP_CONFLICT = 409;
	const HTTP_GONE = 410;
	const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
	const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
	const HTTP_LOCKED = 423;

    const HTTP_INTERNAL_SERVER_ERROR = 500;

	/**
	 * @param array $data
	 */
	public function __construct(array $data = NULL)
	{
		$this->data = $data;
	}

	/**
	 * Shortcut for obtaining an instance.
	 * @param array $data
	 * @return Response
	 */
	public static function data(array $data = NULL)
	{
		return new self($data);
	}

	/**
	 * @param int $code
	 * @return Response
	 */
	public function setHttpStatus($code)
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return Response
	 */
	public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$xHeaders = [];
		foreach ($this->headers as $name => $value) {
			if (substr($name, 0, 2) === 'X-') $xHeaders[] = $name;
			$httpResponse->setHeader($name, $value);
		}

		if ($xHeaders) $httpResponse->setHeader('Access-Control-Expose-Headers', implode(', ', $xHeaders));

		if (is_array($this->data)) {
			$httpResponse->setCode($this->code ?: 200);
			(new JsonResponse($this->data))->send($httpRequest, $httpResponse);
		} else {
			$httpResponse->setCode($this->code ?: 204);
			(new TextResponse($this->data))->send($httpRequest, $httpResponse);
		}
	}

}
