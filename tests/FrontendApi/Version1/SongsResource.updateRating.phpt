<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use App\Model\Entity\SongRating;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Routing\AuthorizationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType(EntityManager::class);

$data = [
    "comment" => "Můj upravený komentář",
    "rating" => 3,
];


//Test unlogged user.
$request = RequestBuilder::target('frontend', 1, 'songs', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setParams(["id" => 1, "ratingId" => 1])
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::class)->find(1));

$request = RequestBuilder::target('frontend', 1, 'songs', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParams(["id" => 1, "ratingId" => 1])
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthorizationException::class);

$sessionToken = logUserIn($em->getDao(User::class)->find(2));

//Rating doesn't exist
$request = RequestBuilder::target('frontend', 1, 'songs', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParams(["id" => 1, "ratingId" => 5])
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_SONG_RATING',
        'message' => 'Song rating with given id not found.'
    ]);

//Test update rating.
$request = RequestBuilder::target('frontend', 1, 'songs', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "ratingId" => 1])
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"      => 1
    ]);
//read updated information
$request = RequestBuilder::target('frontend', 1, 'songs', 'readRating', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "ratingId" => 1])
    ->create(); // create request

$response = handleRequest($request);

$rating = $em->getDao(SongRating::class)->find(1);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"       => 1,
        "comment"  => "Můj upravený komentář",
        "rating"   => 3,
        "created"  => "2014-11-03 11:45:07",
        "modified" => $rating->modified->format('Y-m-d H:i:s')
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');