<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Routing\AuthenticationException;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'readAll', RequestBuilder::METHOD_GET) // specify target
->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthenticationException');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'songbooks', 'readAll', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken) // set session token
->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        [
            "id"    => 2,
            "name"  => "Mé nejoblíbenější",
            "note"  => "pohoda",
            "username" => "Franta",
            "tags" => []
        ],
        [
            "id"    => 1,
            "name"  => "Muj zpěvník na vodu",
            "note"  => "Tohle je nářez",
            "username" => "Franta",
            "tags" => []
        ]
    ]);