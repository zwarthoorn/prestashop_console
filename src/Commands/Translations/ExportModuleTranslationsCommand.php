<?php

namespace Flooris\Prestashop\Commands\Translations;

use Db;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportModuleTranslationsCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('translations:module-export')
            ->setDescription('Export translation source files for installed modules')
            ->addArgument(
                'source_language',
                InputArgument::REQUIRED,
                'ISO Code of the source language (nl, fr, en)'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target directory where the export will be placed',
                './exports/modules'
            )
            ->addOption(
                'id_shop',
                null,
                InputOption::VALUE_REQUIRED,
                'Only export modules that are installed for this shop ID',
                1
            )
            ->addOption(
                'theme',
                null,
                InputOption::VALUE_REQUIRED,
                'Also export translations that are found in this theme'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $this->input->getArgument('target');
        $source_language = $this->input->getArgument('source_language');
        $id_shop = (int)$this->input->getOption('id_shop');
        $theme = $this->input->getOption('theme');

        // Ensure the target path always ends with a /
        $target .= ends_with($target, '/') ? '' : '/';

        // Create dir when not existing
        if ( ! is_dir($target)) {
            if ($this->output->isVerbose()) {
                $this->output->writeln("<info>Creating path {$target}</info>");
            }

            mkdir($target, 0777, true);
        }

        $installed_modules = $this->getInstalledModules($id_shop);
        $installed_modules = collect($installed_modules);

        $installed_modules->each(function($module) use ($target, $source_language, $theme) {

            $name = $module['name'];
            $paths = [
                '/modules/' . $name . '/translations/',
                '/modules/' . $name . '/',
            ];

            if( $theme ) {
                $paths[] = '/themes/' . $theme . '/modules/' . $name . '/translations/';
                $paths[] = '/themes/' . $theme . '/modules/' . $name . '/';
            }

            foreach($paths as $path) {
                $filename = $this->ps_basedir . $path . $source_language . '.php';

                if( file_exists($filename) ) {
                    $this->output->writeln("<info>Found translation in {$path}</info>");

                    $target_dir = $target . $path;
                    if( ! is_dir($target_dir) ) {
                        mkdir($target_dir, 0777, true);
                    }

                    copy($filename, $target_dir . $source_language . '.php');
                }
            }
        });
    }

    protected function getInstalledModules($id_shop)
    {
        $sql = '
            SELECT module.`name` 
            FROM '._DB_PREFIX_.'module AS module
            LEFT JOIN '._DB_PREFIX_.'module_shop AS module_shop ON (module_shop.id_module = module.id_module)
            WHERE module_shop.id_shop = '.(int)$id_shop;

        $modules = Db::getInstance()->executeS($sql);

        return $modules;
    }
}
