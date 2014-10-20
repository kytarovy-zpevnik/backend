<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Model\Entity\User;
use App\Model\Service\SessionService;
use Kdyby\Doctrine\Connection;
use Kdyby\Doctrine\EntityManager;
use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Resource\ResourceFactory;

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer update --dev`\n";
	exit(1);
}

Tester\Environment::setup();

$configurator = new Nette\Configurator;
$configurator->setDebugMode(FALSE);
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../app')
	->addDirectory(__DIR__ . '/../vendor/others')
	->register();

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');
$configurator->addConfig(__DIR__ . '/testConfig.neon');

$dic = $configurator->createContainer();

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function loadSqlDump($file)
{
	global $dic;

	/** @var Connection $connection */
	$connection = $dic->getByType(Connection::class);

	$connection->query('DROP DATABASE ' . $connection->getDatabase() . '; CREATE DATABASE '. $connection->getDatabase() . ' COLLATE utf8_czech_ci; USE ' . $connection->getDatabase());

	$connection->query(file_get_contents($file));
}

/**
 * @param User $user
 * @return string
 */
function logUserIn(User $user)
{
	global $dic;

	$session = $dic->getByType(SessionService::class)->create($user);

	$dic->getByType(EntityManager::class)->flush();

	return $session->token;
}

/**
 * @param Request $request
 * @return Markatom\RestApp\Resource\IResource
 */
function handleRequest(Request $request)
{
	global $dic;

	$resourceFactory = $dic->getByType(ResourceFactory::class);

	$resource = $resourceFactory->create($request->getApiName(), $request->getResourceName(), $request->getApiVersion());

	$response = $resource->handle($request);

	$method = new ReflectionMethod($response, 'setDefaults');
	$method->setAccessible(TRUE);
	$method->invoke($response);

	return $response;
}
