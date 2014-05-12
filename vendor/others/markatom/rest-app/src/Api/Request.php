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
    private $name;

    /** @var string */
    private $method;

    /** @var array */
    private $headers;

    /** @var array */
    private $params;

    /** @var array */
    private $query;

    /** @var array */
    private $post;

    /** @var \Nette\Http\FileUpload[] */
    private $files;

    /**
     * @param string $name
     * @param string $method
     * @param array $headers
     * @param array $params
     * @param array $query
     * @param array $post
     * @param FileUpload[] $files
     */
    public function __construct($name, $method, array $headers, array $params, array $query, $post, array $files)
    {
        $this->name    = $name;
        $this->method  = $method;
        $this->headers = $headers;
        $this->params  = $params;
        $this->query   = $query;
        $this->post    = $post;
        $this->files   = $files;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($name, $default = NULL)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];

        } else {
            return $default;
        }
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
        if (isset($this->params[$name])) {
            return $this->params[$name];

        } else {
            return $default;
        }
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
     * @return array|mixed
     */
    public function getQuery($name = NULL, $default = NULL)
    {
        if ($name === NULL) {
            return $this->query;

        } elseif (isset($this->query[$name])) {
            return $this->query[$name];

        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     * @param mixed $default
     * @throws \InvalidArgumentException
     * @return array|mixed
     */
    public function getPost($name = NULL, $default = NULL)
    {
        if (is_array($this->post)) {
            if ($name === NULL) {
                return $this->post;

            } elseif (isset($this->post[$name])) {
                return $this->post[$name];

            } else {
                return $default;
            }

        } else {
            if ($name !== NULL || $default !== NULL) {
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
        if (isset($this->files[$name])) {
            return $this->files[$name];

        } else {
            return NULL;
        }
    }

    /**
     * @return \Nette\Http\FileUpload[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return string|NULL
     */
    public function getApiName()
    {
        return $this->getParam('api');
    }

    /**
     * @return string|NULL
     */
    public function getApiVersion()
    {
        return $this->getParam('version');
    }

    /**
     * @return string|NULL
     */
    public function getResourceName()
    {
        return $this->getParam('resource');
    }

}
