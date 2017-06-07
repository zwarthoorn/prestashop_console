<?php

namespace Flooris\Prestashop\Commands\Translations;

use Db;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportMetaTranslationsCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('translations:meta-export')
            ->setDescription('Create an export of META data')
            ->addOption(
                'id_lang',
                null,
                InputOption::VALUE_REQUIRED,
                'Language ID that is used as the export source',
                1
            )
            ->addOption(
                'id_shop',
                null,
                InputOption::VALUE_REQUIRED,
                'Shop ID that is used as the export source',
                1
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target directory where the export will be placed',
                './exports/meta'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $this->input->getArgument('target');
        $id_lang = (int)$this->input->getOption('id_lang');
        $id_shop = (int)$this->input->getOption('id_shop');

        // Ensure the target path always ends with a /
        $target .= ends_with($target, '/') ? '' : '/';

        // Create dir when not existing
        if( ! is_dir($target) ) {
            if( $this->output->isVerbose() ) $this->output->writeln("<info>Creating path {$target}</info>");

            mkdir($target, 0777, true);
        }

        // Get a list of the available CMS pages
        $page_meta = $this->getPageMeta($id_lang, $id_shop);
        $page_meta = collect($page_meta);

        // Save all pages and their data in a folder
        $page_meta->each(function($meta) use ($target) {
            $target .= (int)$meta['id_meta'] . '.json';

            $data = [
                'title' => $meta['title'],
                'description' => $meta['description'],
                'keywords' => $meta['keywords'],
                'url_rewrite' => $meta['url_rewrite']
            ];

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving Content for CMS {$meta['id_cms']}</info>");
            file_put_contents(
                $target,
                json_encode($data, JSON_PRETTY_PRINT)
            );
        });
    }

    protected function getPageMeta($id_lang, $id_shop)
    {
        $sql = '
            SELECT id_meta, title, description, keywords, url_rewrite 
            FROM '._DB_PREFIX_.'meta_lang
            WHERE id_lang = '.(int)$id_lang .'
            AND id_shop = '.(int)$id_shop;

        $pages = Db::getInstance()->executeS($sql);

        return $pages;
    }

}
