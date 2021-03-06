<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use App\Model\Entity\SongComment;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Routing\AuthorizationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$data = [
    "comment" => "Můj upravený komentář",
];


//Test unlogged user.
$request = RequestBuilder::target('frontend', 1, 'songs', 'updateComment', RequestBuilder::METHOD_PUT) // specify target
    ->setParams(["id" => 1, "relationId" => 1])
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthenticationException');

//Test unauthorized user
$sessionToken = logUserIn($em->getDao(User::getClassName())->find(1));

$request = RequestBuilder::target('frontend', 1, 'songs', 'updateComment', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthorizationException');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

//Rating doesn't exist
$request = RequestBuilder::target('frontend', 1, 'songs', 'updateComment', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->setParams(["id" => 1, "relationId" => 5])
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_NOT_FOUND)
    ->assertJson([
        'error' => 'UNKNOWN_SONG_COMMENT',
        'message' => 'Song comment with given id not found.'
    ]);

//Test update rating.
$request = RequestBuilder::target('frontend', 1, 'songs', 'updateComment', RequestBuilder::METHOD_PUT) // specify target
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
$request = RequestBuilder::target('frontend', 1, 'songs', 'readComment', RequestBuilder::METHOD_GET) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setParams(["id" => 1, "relationId" => 1])
    ->create(); // create request

$response = handleRequest($request);

$comment = $em->getDao(SongComment::getClassName())->find(1);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"       => 1,
        "comment"  => "Můj upravený komentář",
        "created"  => "2014-11-10 15:44:08",
        "modified" => $comment->modified->format('Y-m-d H:i:s'),
        "username" => "Franta"
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');