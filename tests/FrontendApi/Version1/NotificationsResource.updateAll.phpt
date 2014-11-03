<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\Notification;
use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

/** @var EntityManager $em */
$em = $dic->getByType(EntityManager::class);

$data = ['read' => TRUE];

//////////////////////////////////////////////////////////////////////////////////////////////////

$request = RequestBuilder::target('frontend', 1, 'notifications', 'updateAll', RequestBuilder::METHOD_PUT) // specify target
	->setJsonPost($data)
    ->create(); // create request, not logged in

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);

//////////////////////////////////////////////////////////////////////////////////////////////////

$user = $em->getDao(User::class)->find(3);

$sessionToken = logUserIn($user); // user markatom

$request = RequestBuilder::target('frontend', 1, 'notifications', 'updateAll', RequestBuilder::METHOD_PUT) // specify target
	->setHeader('X-Session-Token', $sessionToken)// set session token
	->setJsonPost($data)
	->create(); // create request, not logged in

$notifications = $em->getDao(Notification::class)->findBy([
	'user' => $user,
	'read' => FALSE
]);

Assert::notEqual(0, count($notifications));

$response = handleRequest($request);

ResponseTester::test($response)
	->assertHttpStatus(ResponseTester::HTTP_NO_CONTENT);

$notifications = $em->getDao(Notification::class)->findBy([
	'user' => $user,
	'read' => FALSE
]);

Assert::equal(0, count($notifications));
