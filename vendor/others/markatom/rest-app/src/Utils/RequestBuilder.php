<?php

namespace Markatom\RestApp\Utils;

use Markatom\RestApp\Api\Request;
use Nette\Object;

/**
 * Request builder for testing your resources.
 * @author Tomáš Markacz
 */
class RequestBuilder extends Object
{

	/** @see {self::__construct} */
	const METHOD_POST = 'post';
	const METHOD_GET = 'get';
	const METHOD_PUT = 'put';
	const METHOD_DELETE = 'delete';

	/** @var string */
	private $apiName;

	/** @var mixed */
	private $apiVersion;

	/** @var string */
	private $resourceName;

	/** @var string */
	private $handlerName;

	/** @var string */
	private $method;

	/** @var array */
	private $headers = [];

	/** @var array */
	private $params = [];

	/** @var array */
	private $query = [];

	/** @var array|string */
	private $post = '';

	/**
	 * @param string $apiName
	 * @param mixed $apiVersion
	 * @param string $resourceName
	 * @param string $handlerName
	 * @param string $method
	 */
	public function __construct($apiName, $apiVersion, $resourceName, $handlerName, $method)
	{
		$this->apiName      = $apiName;
		$this->apiVersion   = $apiVersion;
		$this->resourceName = $resourceName;
		$this->handlerName  = $handlerName;
		$this->method       = $method;
	}

	/**
	 * @param string $apiName
	 * @param mixed $apiVersion
	 * @param string $resourceName
	 * @param string $handlerName
	 * @param string $method
	 * @return self
	 */
	public static function target($apiName, $apiVersion, $resourceName, $handlerName, $method)
	{
		return new self($apiName, $apiVersion, $resourceName, $handlerName, $method);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return self
	 */
	public function setHeader($name, $value)
	{
		$name = strtolower($name);

		$this->headers[$name] = $value;

		return $this;
	}

	/**
	 * @param array $headers
	 * @return self
	 */
	public function setHeaders(array $headers)
	{
		$lowerCased = [];
		foreach (array_keys($headers) as $name => $value) {
			$lowerCased[strtolower($name)] = $value;
		}

		$this->headers = $lowerCased;

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return self
	 */
	public function setParam($name, $value)
	{
		$this->params[$name] = $value;

		return $this;
	}

	/**
	 * @param array $params
	 * @return self
	 */
	public function setParams(array $params)
	{
		$this->params = $params;

		return $this;
	}

	/**
	 * @param array|string $nameOrArray
	 * @param string $value
	 * @return self
	 */
	public function setQuery($nameOrArray, $value = NULL)
	{
		if (is_array($nameOrArray)) {
			$this->query = $nameOrArray;

		} else {
			$this->query[$nameOrArray] = $value;
		}

		return $this;
	}

	/**
	 * @param string $data
	 * @return self
	 */
	public function setRawPost($data)
	{
		$this->post = $data;

		return $this;
	}

	/**
	 * @param array $data
	 * @return self
	 */
	public function setFormEncodedPost(array $data)
	{
		$this->setHeader('content-type', 'application/x-www-form-urlencoded');

		$this->post = $data;

		return $this;
	}

	/**
	 * @param array $data
	 * @return self
	 */
	public function setJsonPost(array $data)
	{
		$this->setHeader('content-type', 'application/json');

		$this->post = $data;

		return $this;
	}

	/**
	 * @return Request
	 */
	public function create()
	{
		return new Request($this->apiName, $this->apiVersion, $this->resourceName, $this->handlerName, $this->method, $this->headers, $this->params, $this->query, $this->post, []);
	}

}
