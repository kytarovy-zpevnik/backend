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

$request = RequestBuilder::target('frontend', 1, 'wishes', 'readAll', RequestBuilder::METHOD_GET) // specify target
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthenticationException');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(1));

$request = RequestBuilder::target('frontend', 1, 'wishes', 'readAll', RequestBuilder::METHOD_GET) // specify target
    ->setHeader('X-Session-Token', $sessionToken) // set session token
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        [
            "id"      => 1,
            "name"    => "Bílá orchidej",
            "interpret" => "Eva a Vašek",
            "note"    => "Co nejdriv",
            "created" => "2014-10-18 10:43:00",
            "modified" => "2014-10-18 10:43:00"
        ],
        [
            "id"      => 2,
            "name"    => "Milionář",
            "interpret" => "Jaromír Nohavica",
            "note"    => "jeste driv",
            "created" => "2014-10-18 10:45:00",
            "modified" => "2014-10-18 10:45:00"
        ]
    ]);