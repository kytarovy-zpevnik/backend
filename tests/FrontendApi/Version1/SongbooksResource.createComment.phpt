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

$data = [
    "comment" => "Super"
];

//Test unlogged user.

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'createComment', RequestBuilder::METHOD_POST) // specify target
    ->setParam("id", 2)
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthenticationException');


//Test unauthorized user and private song.

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(1));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'createComment', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 2)
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthorizationException');

//songbook doesn't exist

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'createComment', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken) // set session token
    ->setParam("id", 10)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
->assertJson([
        "error"   => "UNKNOWN_SONGBOOK",
        "message" => "Songbook with given id not found."
    ]);

//Test created rating.

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'createComment', RequestBuilder::METHOD_POST) // specify target
->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 2)
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id" => 3,
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');
