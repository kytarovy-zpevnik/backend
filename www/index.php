<?php

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

$container = require __DIR__ . '/../app/bootstrap.php';

$application = $container->getService('restApp.application');
$application->onError[] = $application->defaultErrorHandler;
$application->run();
