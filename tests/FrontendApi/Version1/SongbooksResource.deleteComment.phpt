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

$em = $dic->getByType(EntityManager::getClassName());

//Test unlogged user.

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'deleteComment', RequestBuilder::METHOD_DELETE) // specify target
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::getClassName());

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::getClassName())->find(3));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'deleteComment', RequestBuilder::METHOD_DELETE) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthorizationException::getClassName());


$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

//Rating doesn't exist
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'deleteComment', RequestBuilder::METHOD_DELETE) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 100])
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_SONGBOOK_COMMENT',
        'message' => 'Songbook comment with given id not found.'
    ]);

//Test delete comment.
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'deleteComment', RequestBuilder::METHOD_DELETE) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NO_CONTENT);

//delete again comment.
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'deleteComment', RequestBuilder::METHOD_DELETE) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_SONGBOOK_COMMENT',
        'message' => 'Songbook comment with given id not found.'
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');
