<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Markatom\RestApp\Routing\AuthorizationException;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType(EntityManager::class);

$sessionToken = logUserIn($em->getDao(User::class)->find(3)); // user markatom, registered

$request = RequestBuilder::target('frontend', 1, 'users', 'readAll', RequestBuilder::METHOD_GET) // specify target
	->setHeader('X-Session-Token', $sessionToken) // set session token
	->create(); // create request

Assert::exception(function () use ($request) {
	handleRequest($request);
}, AuthorizationException::class);

$sessionToken = logUserIn($em->getDao(User::class)->find(1)); // user pepa, admin

$request = RequestBuilder::target('frontend', 1, 'users', 'readAll', RequestBuilder::METHOD_GET) // specify target
	->setHeader('X-Session-Token', $sessionToken) // set session token
	->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
	->assertHttpStatus(ResponseTester::HTTP_OK)
	->assertJson([
		[
			"id"        => 1,
			"username"  => "Pepa admin",
			"email"     => "pepa@admin.org",
			"lastLogin" => "2014-10-18 10:43:00",
			"role"      => [
				"id"   => 1,
				"name" => "Administrátor",
				"slug" => "admin"
			]
		],
		[
			"id"        => 2,
			"username"  => "Franta",
			"email"     => "franta@co-sel-okolo.cz",
			"lastLogin" => null,
			"role"      => [
				"id"   => 2,
				"name" => "Registrovaný",
				"slug" => "registered"
			]
		],
		[
			"id"        => 3,
			"username"  => "markatom",
			"email"     => "tomas.markacz@gmail.com",
			"lastLogin" => "2014-10-16 20:34:39",
			"role"      => [
				"id"   => 2,
				"name" => "Registrovaný",
				"slug" => "registered"
			]
		]

	]);