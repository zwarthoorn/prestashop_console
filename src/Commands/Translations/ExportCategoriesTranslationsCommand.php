<?php

namespace Flooris\Prestashop\Commands\Translations;

use Db;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCategoriesTranslationsCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('translations:categories-export')
            ->setDescription('Create an export of Category language data')
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
                './exports/categories'
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

        // Get a list of the available Categories
        $categories = $this->getCategories($id_lang, $id_shop);
        $categories = collect($categories);

        // Save all Categories and their data in a folder
        $categories->each(function($category) use ($target) {
            $target .= (int)$category['id_category'];

            // Create dir for the CMS page when it doesn't exist yet
            if( ! is_dir($target) ) {
                if( $this->output->isVerbose() ) $this->output->writeln("<info>Creating path {$target}</info>");

                mkdir($target);
            }

            $meta = [
                'name' => $category['name'],
                'title' => $category['meta_title'],
                'description' => $category['meta_description'],
                'keywords' => $category['meta_keywords'],
                'friendly_url' => $category['link_rewrite'],
            ];

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving MetaData for Category {$category['id_category']}</info>");
            file_put_contents(
                $target . '/metadata.json',
                json_encode($meta, JSON_PRETTY_PRINT)
            );

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving Description 1 for Category {$category['id_category']}</info>");
            file_put_contents(
                $target . '/description_1.htm',
                $category['description']
            );

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving Description 2 for Category {$category['id_category']}</info>");
            file_put_contents(
                $target . '/description_2.htm',
                $category['description2']
            );

            if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving Description 3 for Category {$category['id_category']}</info>");
            file_put_contents(
                $target . '/description_3.htm',
                $category['description3']
            );
        });
    }

    protected function getCategories($id_lang, $id_shop)
    {
        $sql = '
            SELECT *
            FROM '._DB_PREFIX_.'category_lang AS category_lang
            RIGHT JOIN '._DB_PREFIX_.'category_shop AS category_shop ON (category_shop.id_shop = category_lang.id_shop)
            JOIN '._DB_PREFIX_.'category AS category ON (category.id_category = category_lang.id_category)
            WHERE category_lang.id_lang = '.(int)$id_lang.'
            AND category_lang.id_shop = '.(int)$id_shop.'
            AND NOT category.is_root_category
            AND category.active';

        $categories = Db::getInstance()->executeS($sql);

        return $categories;
    }

}
