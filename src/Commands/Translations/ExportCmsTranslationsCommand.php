<?php

namespace Flooris\Prestashop\Commands\Translations;

use Db;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCmsTranslationsCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('translations:cms-export')
            ->setDescription('Create an export of CMS language data')
            ->addOption(
                'id_lang',
                null,
                InputOption::VALUE_REQUIRED,
                'Language ID that is used as the export source',
                1
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target directory where the export will be placed',
                './exports/cms'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $this->input->getArgument('target');
        $id_lang = (int)$this->input->getOption('id_lang');

        // Ensure the target path always ends with a /
        $target .= ends_with($target, '/') ? '' : '/';

        // Create dir when not existing
        if( ! is_dir($target) ) {
            if( $this->output->isVerbose() ) $this->output->writeln("<info>Creating path {$target}</info>");

            mkdir($target, 0777, true);
        }

        // Get a list of the available CMS pages
        $pages = $this->getCmsPages($id_lang);
        $pages = collect($pages);

        // Save all pages and their data in a folder
        $pages->each(function($page) use ($target) {
            $target .= (int)$page['id_cms'];

            // Create dir for the CMS page when it doesn't exist yet
            if( ! is_dir($target) ) {
                if( $this->output->isVerbose() ) $this->output->writeln("<info>Creating path {$target}</info>");

                mkdir($target);
            }

            $meta = [
                'title' => $page['meta_title'],
                'description' => $page['meta_description'],
                'keywords' => $page['meta_keywords'],
                'friendly_url' => $page['link_rewrite'],
            ];

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving MetaData for CMS {$page['id_cms']}</info>");
            file_put_contents(
                $target . '/metadata.json',
                json_encode($meta, JSON_PRETTY_PRINT)
            );

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving Content for CMS {$page['id_cms']}</info>");
            file_put_contents(
                $target . '/content.htm',
                $page['content']
            );
        });
    }

    protected function getCmsPages($id_lang)
    {
        $sql = '
            SELECT cms_lang.id_cms, meta_title, meta_description, meta_keywords, content, link_rewrite
            FROM '._DB_PREFIX_.'cms_lang AS cms_lang
            WHERE cms_lang.id_lang = '.(int)$id_lang;

        $pages = Db::getInstance()->executeS($sql);

        return $pages;
    }

}
