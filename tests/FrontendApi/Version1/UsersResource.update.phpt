<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Markatom\RestApp\Routing\AuthorizationException;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(3)); // user markatom, registered

$request = RequestBuilder::target('frontend', 1, 'users', 'update', RequestBuilder::METHOD_PUT) // specify target
    ->setParam("id",3)
    ->setHeader('X-Session-Token', $sessionToken) // set session token
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthorizationException');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(1)); // user pepa, admin

//Test update user
$data = [
    "role" => [
        "slug" => "admin"
    ]
];

$request = RequestBuilder::target('frontend', 1, 'users', 'update', RequestBuilder::METHOD_PUT) // specify target
    ->setHeader('X-Session-Token', $sessionToken) // set session token
    ->setParam("id",3)
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);


ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id"        => 3,
        "username"  => "markatom",
        "email"     => "tomas.markacz@gmail.com",
        "lastLogin" => "2014-10-16 20:34:39",
        "role"      => [
            "id"   => 1,
            "name" => "Administrátor",
            "slug" => "admin"
        ]
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');