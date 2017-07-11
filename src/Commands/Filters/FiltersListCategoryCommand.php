<?php

namespace Flooris\Prestashop\Commands\Filters;

use Flooris\Prestashop\PrestashopHelper;
use Symfony\Component\Console\Helper\Table;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersListCategoryCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('filters:list-category')
            ->setDescription('List all filter configurations for a category')
            ->addArgument('id_category', InputArgument::REQUIRED, 'Category ID')
            ->addArgument('id_shop', InputArgument::OPTIONAL, 'Shop ID', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id_category    = (int)$input->getArgument('id_category');
        $id_shop        = (int)$input->getArgument('id_shop');

        $filters = PrestashopHelper::GetCategoryFilters($id_category, $id_shop);

        $table = new Table($output);
        $table->setHeaders(array_keys($filters->first()));

        $filters->each(function($filter) use ($table) {
            $table->addRow($filter);
        });

        $table->render();
    }
}
