<?php

namespace Flooris\Prestashop\Commands\Filters;

use Flooris\Prestashop\PrestashopHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersExportCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('filters:export')
            ->setDescription('List and export all filter configurations')
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target directory where the export will be placed',
                './exports/filters.json'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filters = PrestashopHelper::GetFilterConfigurations();

        $this->listAllFilterConfigurations($filters);

        $this->writeToJson($filters);


    }

    protected function listAllFilterConfigurations($filters)
    {
        $table = new Table($this->output);
        $table->setHeaders(array_keys($filters->first()));

        $filters->each(function($filter) use ($table) {
            $table->addRow($filter);
        });

        $table->render();
    }

    protected function writeToJson($filters)
    {
        $filters_config = [];

        foreach ($filters as $filter) {
            $filter_config      = $this->getFilterConfig($filter);
            $filters_config[]   = [
                'id_layered_filter' => (int)$filter['id_layered_filter'],
                'name'      => $filter['name'],
                'filters'   => $filter_config
            ];
        }

        $target = $this->input->getArgument('target');

        if( $this->output->isVerbose() ) $this->output->writeln("<info>Saving filters config</info>");
        file_put_contents(
            $target,
            json_encode($filters_config, JSON_PRETTY_PRINT)
        );

        $this->output->writeln("<info>Export saved to: {$target}</info>");
    }

    protected function getFilterConfig($filter)
    {
        $filter_config_unserialized      = unserialize($filter['filters']);

        $filter_config = [
            'categories' => $filter_config_unserialized['categories'],
            'shop_list' => $filter_config_unserialized['shop_list'],
            'filters' => []
        ];

        foreach ($filter_config_unserialized as $key => $filter_config_item)
        {
            $filter = $this->getFilter($key);
            if ( ! $filter ) {
                continue;
            }

            $filter_config['filters'][] = $filter;
        }

        return $filter_config;
    }


    /**
     * Get Filter
     *
     * @param string $type_key
     * @return array|bool
     */
    protected function getFilter($type_key = '')
    {
        $matches = [];
        preg_match("/^layered_([a-z]+)_([a-z]+)_([a-z0-9]+)$/i", $type_key, $matches);

        if (empty($matches)){
            preg_match("/^layered_([a-z]+)_([a-z]+)$/i",$type_key,$matches);
        }

        if ( ! $matches || ! count($matches) ) {
            return false;
        }

        $type_shortkey = $matches[2];
        $value = $matches[3];

        $type = false;
        switch ($type_shortkey) {
            case 'price':
                $type = "price";
                break;
            case 'subcategories':
                $type = "category";
                break;
            case 'ag':
                $type = "id_attribute_group";
                break;
            case 'feat':
                $type = "id_feature";
                break;
            case 'stock':
                $type = "quantity";
                break;
            case 'manufacturer':
                $type = "manufacturer";
                break;
            case 'condition':
                $type = "condition";
                break;
            case 'weight':
                $type = "weight";
                break;

        }

        $filter = [
            'type' => $type,
            'value' => (int)$value
        ];

        return $filter;
    }

}
