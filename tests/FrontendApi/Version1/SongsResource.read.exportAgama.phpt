<?php

require __DIR__ . '/../../bootstrap.php';

use App\Model\Entity\User;
use Markatom\RestApp\Utils\RequestBuilder;
use Markatom\RestApp\Utils\ResponseTester;
use Nette\Utils\Json;
use Tester\Assert;

loadSqlDump(__DIR__ . '/../../files/dump.sql');

$em = $dic->getByType('Kdyby\Doctrine\EntityManager');

$sessionToken = logUserIn($em->getDao(User::getClassName())->find(2));

$request = RequestBuilder::target('frontend', 1, 'songs', 'read', RequestBuilder::METHOD_GET)
	->setHeader('X-Session-Token', $sessionToken)
	->setParams(['id' => 8])
	->setQuery(['export' => 'agama'])
	->create();

$response = handleRequest($request);

$expected = <<<END
G     D                      Ami F
Mama, take this badge off of m---e
G       D         C
I can't use it anymore
G            D                       Ami
It's gettin' dark, to dark for me to see
G             D                    C
Feel like I'm knockin' on heaven's door

Knock, knock, knockin' on heaven's door
Knock, knock, knockin' on heaven's door
Knock, knock, knockin' on heaven's door
Knock, knock, knockin' on heaven's door

Mama, put my guns on the ground
I can't shoot them any more
That long black cloud is comin' down
Feels like I'm knockin' on heaven's door

Knock, knock, knockin' on heaven's door
Knock, knock, knockin' on heaven's door
Knock, knock, knockin' on heaven's door

END;

$agama = Json::decode($response->getData())->agama;

$agama = implode("\n", array_map('trim', explode("\n", $agama))); // trim lines

Assert::equal($expected, $agama);
