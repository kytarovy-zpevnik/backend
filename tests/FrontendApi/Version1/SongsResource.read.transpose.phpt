<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'songs', 'read', RequestBuilder::METHOD_GET)
	->setHeader('X-Session-Token', $sessionToken)
	->setParams(['id' => 8])
	->setQuery(['transpose' => 3])
	->create();

$response = handleRequest($request);

ResponseTester::test($response)
	->assertJson([
		"id" => 8,
		"title" => "Knocking on Heaven's Door",
		"album" => "Pat Garrett & Billy the Kid",
		"author" => "Bob Dylan",
		"originalAuthor" => NULL,
		"year" => 1973,
		"lyrics" => "((1))\nMama, take this badge off of me \nI can't use it anymore \nIt's gettin' dark, to dark for me to see \nFeel like I'm knockin' on heaven's door \n\n((R))\nKnock, knock, knockin' on heaven's door \nKnock, knock, knockin' on heaven's door \nKnock, knock, knockin' on heaven's door \nKnock, knock, knockin' on heaven's door \n\n((2))\nMama, put my guns on the ground \nI can't shoot them any more \nThat long black cloud is comin' down \nFeels like I'm knockin' on heaven's door \n\n((R))\nKnock, knock, knockin' on heaven's door \nKnock, knock, knockin' on heaven's door \nKnock, knock, knockin' on heaven's door \nKnock, knock, knockin' on heaven's door ",
		"chords" => "{\"0\":\"B\",\"6\":\"F\",\"29\":\"Cmi\",\"32\":\"B\",\"40\":\"F\",\"50\":\"D#\",\"55\":\"B\",\"68\":\"F\",\"92\":\"Cmi\",\"96\":\"B\",\"110\":\"F\",\"131\":\"D#\"}",
		"note" => NULL,
		"public" => TRUE,
		"songbooks" => [],
		"username" => "Franta",
		"tags" => []
	]);
