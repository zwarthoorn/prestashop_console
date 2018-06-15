<?php

namespace Flooris\Prestashop\Commands\Translations;

use Db;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportGenericTranslationsCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('translations:generic-export')
            ->setDescription('Export a given column from a table')
            ->addArgument(
                'table',
                InputArgument::REQUIRED,
                'Table name without the prefix (ps_)'
            )
            ->addArgument(
                'column',
                InputArgument::REQUIRED,
                'Column in the table which contains the content'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Target directory where the export will be placed'
            )
            ->addOption(
                'id_lang',
                null,
                InputOption::VALUE_REQUIRED,
                'ID Of the language that gets exported'
            )
            ->addOption(
                'id_shop',
                null,
                InputOption::VALUE_REQUIRED,
                'ID Of the shop that gets exported'
            )
            ->addOption(
                'id_column',
                null,
                InputOption::VALUE_REQUIRED,
                'Column name that contains the tables primary key, 
                defaults to id_TABLE_NAME'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $this->input->getArgument('target');
        $table = $this->input->getArgument('table');
        $column = $this->input->getArgument('column');

        if( ! $primary_key = $this->input->getOption('id_column') ) {
            $primary_key = 'id_' . $table;
        }

        $id_lang = $this->input->getOption('id_lang');
        $id_shop = $this->input->getOption('id_shop');

        // Ensure the target path always ends with a /
        $target .= ends_with($target, '/') ? '' : '/';

        // Create dir when not existing
        if( ! is_dir($target) ) {
            if( $this->output->isVerbose() ) $this->output->writeln("<info>Creating path {$target}</info>");

            mkdir($target, 0777, true);
        }

        $rows = $this->getRows($table, $column, $primary_key, $id_lang, $id_shop);
        $rows = collect($rows);

        $rows->each(function($row) use ($target) {
            $target .= (int)$row['primary'] . '.txt';

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving Content {$row['primary']}</info>");
            file_put_contents(
                $target,
                $row['content']
            );
        });
    }

    protected function getRows($table, $column, $primary_key, $id_lang, $id_shop)
    {
        $table = _DB_PREFIX_ . $table;

        $sql = "
            SELECT 
                `{$primary_key}` AS `primary`,
                `{$column}` AS `content`
            FROM `{$table}`
            WHERE 1
        ";

        if ( $id_lang ) {
            $sql .= ' AND `id_lang` = '.(int)$id_lang;
        }

        if ( $id_shop ) {
            $sql .= ' AND `id_shop` = '.(int)$id_shop;
        }

        $rows = Db::getInstance()->executeS($sql);

        return $rows;
    }

}
