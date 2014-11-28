<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use App\Model\Entity\SongbookRating;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Routing\AuthorizationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType(EntityManager::getClassName());

$data = [
    "comment" => "Můj upravený komentář",
    "rating" => 3,
];


//Test unlogged user.
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setParams(["id" => 1, "relationId" => 1])
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::getClassName());

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::getClassName())->find(1));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthorizationException::getClassName());

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

//Rating doesn't exist
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParams(["id" => 1, "relationId" => 5])
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_SONGBOOK_RATING',
        'message' => 'Songbook rating with given id not found.'
    ]);

//Test update rating.
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'updateRating', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 1])
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"      => 1
    ]);
//read updated information
$request = RequestBuilder::target('frontend', 1, 'songbooks', 'readRating', RequestBuilder::METHOD_GET) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

$response = handleRequest($request);

$rating = $em->getDao(SongbookRating::getClassName())->find(1);

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