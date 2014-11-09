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

//Test unlogged user.

$request = RequestBuilder::target('frontend', 1, 'songs', 'readRating', RequestBuilder::METHOD_GET) // specify target
    ->setParam("ratingId", 2)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::class)->find(1));

$request = RequestBuilder::target('frontend', 1, 'songs', 'readRating', RequestBuilder::METHOD_GET) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("ratingId", 2)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthorizationException::class);

$sessionToken = logUserIn($em->getDao(User::class)->find(2));

//Rating doesn't exist
$request = RequestBuilder::target('frontend', 1, 'songs', 'readRating', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken)
    ->setParam("ratingId", 5)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_SONG_RATING',
        'message' => 'Song rating with given id not found.'
    ]);

//Test read rating.
$request = RequestBuilder::target('frontend', 1, 'songs', 'readRating', RequestBuilder::METHOD_GET) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParam("ratingId", 1)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"       => 1,
        "comment"  => "Můj první komentář",
        "rating"   => 5,
        "created"  => "2014-11-03 11:45:07",
        "modified" => "2014-11-03 11:45:07"
    ]);