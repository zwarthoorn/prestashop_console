<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Flooris\Prestashop\Commands\Translations\ExportCmsTranslationsCommand;
use Flooris\Prestashop\Commands\Translations\ExportMetaTranslationsCommand;
use Flooris\Prestashop\Commands\Translations\ExportGenericTranslationsCommand;


// Initialize Application
$application = new Application();

// Add commands to the Application
$application->addCommands([
    new ExportCmsTranslationsCommand,
    new ExportGenericTranslationsCommand,
    new ExportMetaTranslationsCommand,
]);

// Return the application
return $application;
