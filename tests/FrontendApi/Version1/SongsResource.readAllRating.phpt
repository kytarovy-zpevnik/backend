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

$em = $dic->getByType(EntityManager::class);

//unlogged user
$request = RequestBuilder::target('frontend', 1, 'songs', 'readAllRating', RequestBuilder::METHOD_GET) // specify target
    ->setParam("id", 2)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);


//unauthorized user
$sessionToken = logUserIn($em->getDao(User::class)->find(1));

$request = RequestBuilder::target('frontend', 1, 'songs', 'readAllRating', RequestBuilder::METHOD_GET) // specify target
    ->setHeader('X-Session-Token', $sessionToken) // set session token
    ->setParam("id", 2)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthorizationException::class);


//songbook doesn't exist

$sessionToken = logUserIn($em->getDao(User::class)->find(2));

$request = RequestBuilder::target('frontend', 1, 'songs', 'readAllRating', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken) // set session token
->setParam("id", 10)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        "error"   => "UNKNOWN_SONG",
        "message" => "Song with given id not found."
    ]);

//read all ratings
$sessionToken = logUserIn($em->getDao(User::class)->find(2));

$request = RequestBuilder::target('frontend', 1, 'songs', 'readAllRating', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken) // set session token
->setParam("id", 2)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        [
            'id'       => 1,
            'comment'  => "Můj první komentář",
            'rating'   => 5,
            'created'  => "2014-11-03 11:45:07",
            'modified' => "2014-11-03 11:45:07"
        ],
        [
            'id'       => 2,
            'comment'  => "Můj druhý komentář",
            'rating'   => 4,
            'created'  => "2014-11-03 11:45:07",
            'modified' => "2014-11-03 11:45:07"
        ]
    ]);