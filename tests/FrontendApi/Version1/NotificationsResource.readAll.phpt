<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType(EntityManager::getClassName());

//////////////////////////////////////////////////////////////////////////////////////////////////

$request = RequestBuilder::target('frontend', 1, 'notifications', 'readAll', RequestBuilder::METHOD_GET) // specify target
    ->create(); // create request, not logged in

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::getClassName());

//////////////////////////////////////////////////////////////////////////////////////////////////

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(3)); // user markatom

$request = RequestBuilder::target('frontend', 1, 'notifications', 'readAll', RequestBuilder::METHOD_GET)// specify target
	->setHeader('X-Session-Token', $sessionToken)// set session token
	->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
	->assertJson([
		[
			'id'      => 1,
			'created' => '2014-11-03 13:53:12',
			'read'    => false,
			'text'    => 'Song notification.',
			'target'  => [
				'song' => [
					'id'    => 2,
					'title' => 'supersong',
				],
			],
		],
		[
			'id'      => 2,
			'created' => '2014-11-03 13:53:28',
			'read'    => false,
			'text'    => 'Songbook notification.',
			'target'  => [
				'songbook' => [
					'id'   => 1,
					'name' => 'Muj zpěvník na vodu',
				],
			],
		],
		[
			'id'      => 3,
			'created' => '2014-11-03 13:53:50',
			'read'    => true,
			'text'    => 'Read notification with no song or songbook.',
			'target'  => NULL,
		],
	]);

//////////////////////////////////////////////////////////////////////////////////////////////////

$request = RequestBuilder::target('frontend', 1, 'notifications', 'readAll', RequestBuilder::METHOD_GET)// specify target
	->setQuery('unread', 1)
	->setHeader('X-Session-Token', $sessionToken)// set session token
	->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
	->assertJson([
		[
			'id'      => 1,
			'created' => '2014-11-03 13:53:12',
			'read'    => false,
			'text'    => 'Song notification.',
			'target'  => [
				'song' => [
					'id'    => 2,
					'title' => 'supersong',
				],
			],
		],
		[
			'id'      => 2,
			'created' => '2014-11-03 13:53:28',
			'read'    => false,
			'text'    => 'Songbook notification.',
			'target'  => [
				'songbook' => [
					'id'   => 1,
					'name' => 'Muj zpěvník na vodu',
				],
			],
		],
	]);

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2)); // user franta

$request = RequestBuilder::target('frontend', 1, 'notifications', 'readAll', RequestBuilder::METHOD_GET)// specify target
	->setHeader('X-Session-Token', $sessionToken)// set session token
	->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
	->assertJson([
		[
			'id'      => 4,
			'created' => '2014-11-03 13:54:09',
			'read'    => TRUE,
			'text'    => 'Another song notification.',
			'target'  => [
				'song' => [
					'id'    => 1,
					'title' => 'Foobar',
				],
			],
		],
	]);

$request = RequestBuilder::target('frontend', 1, 'notifications', 'readAll', RequestBuilder::METHOD_GET)// specify target
	->setQuery('unread', 1)
	->setHeader('X-Session-Token', $sessionToken)// set session token
	->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
	->assertJson([]);
