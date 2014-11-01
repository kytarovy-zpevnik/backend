<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use App\Model\Entity\Wish;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Routing\AuthorizationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType(EntityManager::class);

$data = [
    "name" => "Chci Evu a Vaška",
    "meet" => TRUE,
    "note" => "Uz to mam"
];


//Test unlogged user.
$request = RequestBuilder::target('frontend', 1, 'wishes', 'update', RequestBuilder::METHOD_POST) // specify target
    ->setParam("id", 1)
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::class)->find(2));

$request = RequestBuilder::target('frontend', 1, 'wishes', 'update', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParam("id", 1)

    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthorizationException::class);



$sessionToken = logUserIn($em->getDao(User::class)->find(1));

//Wish doesn't exist
$request = RequestBuilder::target('frontend', 1, 'wishes', 'update', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 5)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_WISH',
        'message' => 'Wish with given id not found.'
    ]);

//Test update wish.
$request = RequestBuilder::target('frontend', 1, 'wishes', 'update', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 1)
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"      => 1
    ]);
//read updated information
$request = RequestBuilder::target('frontend', 1, 'wishes', 'read', RequestBuilder::METHOD_POST) // specify target
->setHeader('X-Session-Token', $sessionToken)
    ->setParam("id", 1)
    ->create(); // create request

$response = handleRequest($request);

$wish = $em->getDao(Wish::class)->find(1);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"      => 1,
        "name"    => "Chci Evu a Vaška",
        "note" => "Uz to mam",
        "meet"    => TRUE,
        "created" => "2014-10-18 10:43:00",
        "modified" => $wish->modified->format('Y-m-d H:i:s')
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');