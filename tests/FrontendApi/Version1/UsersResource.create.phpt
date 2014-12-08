<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$user = $em->getDao(User::getClassName())->find(1);

//Test duplicate username
$data = [
    "username" => $user->username,
    "email" => "takovy@tam.neni",
    "password" => "12345",
];

$request = RequestBuilder::target('frontend', 1, 'users', 'create', RequestBuilder::METHOD_POST) // specify target
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_CONFLICT)
    ->assertJson([
        "error" => "DUPLICATE_USERNAME",
        "message" => "User with given username already created."
    ]);


//Test duplicate email.
$data = [
    "username" => "takovyTamNeni",
    "email" => $user->email,
    "password" => "12345",
];

$request = RequestBuilder::target('frontend', 1, 'users', 'create', RequestBuilder::METHOD_POST) // specify target
->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_CONFLICT)
    ->assertJson([
        "error" => "DUPLICATE_EMAIL",
        "message" => "User with given email already created."
    ]);


//Test create user
$data = [
    "username" => "kytarista666",
    "email" => "kytarista666@gmail.com",
    "password" => "12345",
];

$request = RequestBuilder::target('frontend', 1, 'users', 'create', RequestBuilder::METHOD_POST) // specify target
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
            "id"        => 6,
            "username"  => "kytarista666",
            "email"     => "kytarista666@gmail.com",
            "lastLogin" => NULL,
            "role"      => [
                "id"   => 2,
                "name" => "Registrovaný",
                "slug" => "registered"
            ]
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');