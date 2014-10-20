<?php

namespace Markatom\RestApp\Api;

use Markatom\RestApp\Routing\Route;
use Nette\Http\FileUpload;
use Nette\Object;

/**
 * Api resource request.
 * @author	Tomáš Markacz
 */
class Request extends Object
{

	/** @var string */
	private $apiName;

	/** @var mixed */
	private $apiVersion;

	/** @var string */
	private $resourceName;

	/** @var string */
	private $handlerName;

    /** @var string */
    private $name;

    /** @var string */
    private $method;

    /** @var array */
    private $headers;

    /** @var array */
    private $params;

    /** @var array */
    private $query;

    /** @var array|string */
    private $post;

    /** @var \Nette\Http\FileUpload[] */
    private $files;

	/**
	 * @param string $apiName
	 * @param mixed $apiVersion
	 * @param string $resourceName
	 * @param string $handlerName
	 * @param string $method
	 * @param array $headers
	 * @param array $params
	 * @param array $query
	 * @param array|string $post
	 * @param FileUpload[] $files
	 */
    public function __construct($apiName, $apiVersion, $resourceName, $handlerName, $method, array $headers, array $params, array $query, $post, array $files)
    {
		$this->apiName      = $apiName;
		$this->apiVersion   = $apiVersion;
		$this->resourceName = $resourceName;
		$this->handlerName  = $handlerName;
		$this->method       = $method;
		$this->headers      = $headers;
		$this->params       = $params;
		$this->query        = $query;
		$this->post         = $post;
		$this->files        = $files;
    }

    /**
     * @return string
     */
    public function getName()
    {
		return $this->apiName
			. ($this->apiVersion === NULL ? '' : "($this->apiVersion)")
			. ":$this->resourceName"
			. ":$this->handlerName";
    }

	/**
	 * @return string
	 */
	public function getApiName()
	{
		return $this->apiName;
	}

	/**
	 * @return mixed
	 */
	public function getApiVersion()
	{
		return $this->apiVersion;
	}

	/**
	 * @return string
	 */
	public function getResourceName()
	{
		return $this->resourceName;
	}

	/**
	 * @return string
	 */
	public function getHandlerName()
	{
		return $this->handlerName;
	}

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $name
     * @return mixed
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
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = NULL)
    {
        return array_key_exists($name, $this->params)
            ? $this->params[$name]
            : $default;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getQuery($name = NULL, $default = NULL)
    {
        if ($name === NULL) {
            return $this->query;

        } elseif (array_key_exists($name, $this->query)) {
            return $this->query[$name];

        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     * @param mixed $default
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function getData($name = NULL, $default = NULL)
    {
        if (is_array($this->post)) {
            if ($name === NULL) {
                return $this->post;

            } elseif (array_key_exists($name, $this->post)) {
                return $this->post[$name];

            } else {
                return $default;
            }

        } else {
            if ($name !== NULL) {
                throw new \InvalidArgumentException("Post data is not array. Cannot access them by name.");
            }

            return $this->post;
        }
    }

    /**
     * @param string $name
     * @return \Nette\Http\FileUpload|NULL
     */
    public function getFile($name)
    {
        return array_key_exists($name, $this->files)
            ? $this->files[$name]
			: NULL;
    }

    /**
     * @return \Nette\Http\FileUpload[]
     */
    public function getFiles()
    {
        return $this->files;
    }

}
