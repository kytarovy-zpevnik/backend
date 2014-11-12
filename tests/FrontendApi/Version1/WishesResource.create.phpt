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

//Test unlogged user.
$data = [
    "name" => "Evy a Vaška hip hopová taška",
    "interpret" => "Eva a Vašek",
    "note" => "Dochází mi nápady"
];

$request = RequestBuilder::target('frontend', 1, 'wishes', 'create', RequestBuilder::METHOD_POST) // specify target
    ->setJsonPost($data)
    ->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, AuthenticationException::class);


//Test created wish.
$data = [
    "name" => "Evy a Vaška hip hopová taška",
    "interpret" => "Eva a Vašek",
    "note" => "Dochází mi nápady"
];

$sessionToken = logUserIn($em->getDao(User::class)->find(1));

$request = RequestBuilder::target('frontend', 1, 'wishes', 'create', RequestBuilder::METHOD_POST) // specify target
    ->setHeader('X-Session-Token', $sessionToken)
    ->setJsonPost($data)
    ->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
        "id" => 3,
    ]);

loadSqlDump(__DIR__ . '/../../files/dump.sql');