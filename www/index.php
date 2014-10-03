<?php

use Markatom\RestApp\ErrorHandlers;

$container = require __DIR__ . '/../app/bootstrap.php';

if (php_sapi_name() === 'cli') {
	$container->getService('application')->run();

} else {
	$application = $container->getService('restApp.application');

	ErrorHandlers::register($application);

	$application->run();
}
