<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Routing\AuthorizationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');


//Test unlogged user.
$request = RequestBuilder::target('frontend', 1, 'wishes', 'delete', RequestBuilder::METHOD_POST) // specify target
    ->setParam("id", 1)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthenticationException');

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'wishes', 'delete', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 1)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthorizationException');

//Test update wish.
$sessionToken = logUserIn($em->getDao(User::getClassName())->find(1));

$request = RequestBuilder::target('frontend', 1, 'wishes', 'delete', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 1)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NO_CONTENT);

//delete wish again
$request = RequestBuilder::target('frontend', 1, 'wishes', 'delete', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 1)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_WISH',
        'message' => 'Wish with given id not found.'
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');
