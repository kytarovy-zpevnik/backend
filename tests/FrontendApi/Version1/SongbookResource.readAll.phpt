<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType(EntityManager::class);

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'readAll', RequestBuilder::METHOD_GET) // specify target
->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);

$sessionToken = logUserIn($em->getDao(User::class)->find(2));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'readAll', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken) // set session token
->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        [
            "id"        => 1,
            "owner_id"  => 2,
            "name"     => "Muj zpěvník na vodu",
            "archived" => false,
            "public" => true,
            "created" => "2014-10-20 10:41:00",
            "modified" => "2014-10-20 10:41:00"
        ],
        [
            "id"        => 1,
            "owner_id"  => 2,
            "name"     => "Mé nejoblíbenější",
            "archived" => false,
            "public" => false,
            "created" => "2014-10-20 10:42:01",
            "modified" => "2014-10-20 10:42:02"
        ]
    ]);