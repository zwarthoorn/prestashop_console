<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Flooris\Prestashop\Commands\Translations\ExportCmsTranslationsCommand;


// Initialize Application
$application = new Application();

// Add commands to the Application
$application->addCommands([
    new ExportCmsTranslationsCommand,
]);

// Return the application
return $application;
