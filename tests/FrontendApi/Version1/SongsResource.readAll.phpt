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

$request = RequestBuilder::target('frontend', 1, 'songs', 'readAll', RequestBuilder::METHOD_GET) // specify target
->create(); // create request

Assert::exception(function () use ($request) {
    handleRequest($request);
}, 'Markatom\RestApp\Routing\AuthenticationException');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'songs', 'readAll', RequestBuilder::METHOD_GET) // specify target
->setHeader('X-Session-Token', $sessionToken) // set session token
->create(); // create request

$response = handleRequest($request);

ResponseTester::test($response)
    ->assertHttpStatus(ResponseTester::HTTP_OK)
    ->assertJson([
		[
			"id"        => 3,
			"title"  => "Highway to hell",
			"album"     => NULL,
			"author" => "AC-DC",
			"originalAuthor" => NULL,
			"year" => NULL,
			"note" => "",
            "public" => false,
            "username" => "Franta",
			"tags" => []
		],
		[
			"id"        => 5,
			"title"  => "Hymna",
			"album"     => "České songy",
			"author" => "Miloš Zeman",
			"originalAuthor" => "Josef Kajetán Tyl",
			"year" => 2014,
			"note" => "Lorem ipsum",
            "public" => false,
            "username" => "Franta",
			"tags" => []
		],
		[
			"id"             => 8,
			"title"          => "Knocking on Heaven's Door",
			"album"          => "Pat Garrett & Billy the Kid",
			"author"         => "Bob Dylan",
			"originalAuthor" => NULL,
			"year"           => 1973,
			"note"           => NULL,
			"public"         => TRUE,
			"username"       => "Franta",
			"tags"           => []
		],
        [
            "id"        => 2,
            "title"  => "supersong",
            "album"     => "nejlepší songy",
            "author" => NULL,
            "originalAuthor" => NULL,
            "year" => 2005,
			"note" => "",
            "public" => false,
            "username" => "Franta",
			"tags" => []
        ],
    ]);