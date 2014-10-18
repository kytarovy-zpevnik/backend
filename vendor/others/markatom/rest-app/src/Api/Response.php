<?php

namespace Markatom\RestApp\Api;

use Nette;
use Nette\Application\IResponse;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Object;
use Nette\Utils\Json;

/**
 * Api resource response.
 * @author Tomáš Markacz
 */
class Response extends Object implements IResponse
{

	/** @var string */
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
	private function __construct($data = NULL)
	{
		$this->data = $data;
	}

	/**
	 * @param array $data
	 * @return Response
	 */
	public static function json(array $data)
	{
		$response = new self(Json::encode($data));

		$response->setHeader('content-type', 'application/json');
		$response->disableCache();

		return $response;
	}

	/**
	 * @param array $data
	 * @return Response
	 */
	public static function formEncoded(array $data)
	{
		$response = new self(http_build_query($data));

		$response->setHeader('content-type', 'application/x-www-form-urlencoded');
		$response->disableCache();

		return $response;
	}

	/**
	 * @param string $data
	 * @param string $mimeType
	 * @return Response
	 */
	public static function raw($data, $mimeType = NULL)
	{
		$response = new self((string) $data);

		if ($mimeType) {
			$response->setHeader('content-type', $mimeType);
		}

		$response->disableCache();

		return $response;
	}

	/**
	 * @return Response
	 */
	public static function blank()
	{
		return new self;
	}

	/**
	 * @return string
	 */
	public function getData()
	{
		return $this->data;
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
	 * @return int
	 */
	public function getHttpStatus()
	{
		return $this->code;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return Response
	 */
	public function setHeader($name, $value)
	{
		$name = strtolower($name);

		$this->headers[$name] = $value;

		return $this;
	}

	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getHeader($name)
	{
		$name = strtolower($name);

		return array_key_exists($name, $this->headers)
			? $this->headers[$name]
			: NULL;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 */
	private function setDefaults()
	{
		if (!$this->code) {
			$this->code = $this->data === '' ? self::HTTP_NO_CONTENT : self::HTTP_OK;
		}

		$xHeaders = [];
		foreach ($this->headers as $name => $value) {
			if (substr($name, 0, 2) === 'X-') {
				$xHeaders[] = $name;
			}
		}

		if ($xHeaders) {
			$this->headers['access-control-expose-headers'] = implode(', ', $xHeaders);
		}
	}

	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$this->setDefaults();

		$httpResponse->setCode($this->code);

		foreach ($this->headers as $name => $value) {
			$httpResponse->setHeader($name, $value);
		}

		echo $this->data;
	}

	/**
	 */
	private function disableCache()
	{
		$this->setHeader('cache-control', 'no-cache, no-store, must-revalidate'); // HTTP 1.1
		$this->setHeader('pragma', 'no-cache'); // HTTP 1.0
		$this->setHeader('expires', '0'); // proxies
	}

}
