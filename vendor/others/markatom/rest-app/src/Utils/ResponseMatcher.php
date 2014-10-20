<?php

namespace Markatom\RestApp\Utils;

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer require --dev nette/tester`\n";
	exit(1);
}

use Markatom\RestApp\Api\Response;
use Nette\Object;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\AssertException;

/**
 * Response matcher for testing your resources.
 * @author Tomáš Markacz
 */
class ResponseTester extends Object
{

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

	/** @var Response */
	private $response;

	/**
	 * @param Response $response
	 */
    public function __construct(Response $response)
	{
		$this->response = $response;
	}

	/**
	 * @param Response $response
	 * @return self
	 */
	public static function test(Response $response)
	{
		return new self($response);
	}

	/**
	 * @param string $expected
	 * @throws AssertException
	 * @return self
	 */
	public function assertData($expected)
	{
		Assert::$counter++;

		$actual = $this->response->getData();

		if ($actual !== $expected) {
			Assert::fail("Response data %1 should be %2.", $actual, $expected);
		}

		return $this;
	}

	/**
	 * @param int $expected
	 * @throws AssertException
	 * @return self
	 */
	public function assertHttpStatus($expected)
	{
		Assert::$counter++;

		$actual = $this->response->getHttpStatus();

		if ($actual !== $expected) {
			Assert::fail("Response HTTP status %1 should be %2.", $actual, $expected);
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $expected
	 * @throws AssertException
	 * @return self
	 */
	public function assertHeader($name, $expected = NULL)
	{
		Assert::$counter++;

		$actual = $this->response->getHeader($name);

		if ($expected === NULL && $actual === NULL) {
			Assert::fail("Response header $name expected.");
		}

		if ($expected !== NULL && $actual !== $expected) {
			Assert::fail("Respones header $name with a value %2 was expected but got %1.", $actual, $expected);
		}

		return $this;
	}

	/**
	 * @param array $data
	 * @throws AssertException
	 * @return self
	 */
	public function assertJson(array $data)
	{
		Assert::$counter++;

		$this->assertHeader('content-type', 'application/json');
		$this->assertData(Json::encode($data));

		return $this;
	}

	/**
	 * @param array $data
	 * @throws AssertException
	 * @return self
	 */
	public function assertFormEncoded(array $data)
	{
		Assert::$counter++;

		$this->assertHeader('content-type', 'application/x-www-form-urlencoded');
		$this->assertData(http_build_query($data));

		return $this;
	}

}
