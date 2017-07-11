<?php

namespace Flooris\Prestashop\Commands\Filters;

use Db;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FiltersImportCommand extends PrestashopCommand
{

    protected function configure()
    {
        $this
            ->setName('filters:import')
            ->setDescription('Import filters configuration')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'JSON file which we want to import from'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $this->input->getArgument('file');

        if ( ! file_exists($file) ) {
            throw new \Exception("File does not exists! File path: {$file}");
        }

        $file_contents = file_get_contents($file);
        if ( ! $file_contents ) {
            throw new \Exception("File is empty! File path: {$file}");
        }

        $filters_config = json_decode($file_contents);
        if ( ! $filters_config ) {
            throw new \Exception("File is not a valid JSON format! File path: {$file}");
        }

        Db::getInstance()->execute("TRUNCATE TABLE `"._DB_PREFIX_."layered_category`");
        Db::getInstance()->execute("TRUNCATE TABLE `"._DB_PREFIX_."layered_filter`");
        Db::getInstance()->execute("TRUNCATE TABLE `"._DB_PREFIX_."layered_filter_shop`");


        foreach ($filters_config as $filters_config_item)
        {
            $id_layered_filter = $filters_config_item->id_layered_filter;
            $name = $filters_config_item->name;
            $filter_config = $filters_config_item->filters;

            $filters_serialized = $this->getSerializedFilters($filter_config);
            $categories_count = count($filter_config->categories);

            $result = Db::getInstance()->execute("INSERT INTO `"._DB_PREFIX_."layered_filter` VALUES({$id_layered_filter}, '{$name}', '{$filters_serialized}', $categories_count, NOW())");
//            Db::getInstance()->execute("DELETE FROM `"._DB_PREFIX_."layered_filter_shop`    WHERE `id_layered_filter` = {$id_layered_filter}");

//            $query_insert = "
//                        INSERT INTO `"._DB_PREFIX_."layered_filter`
//                            (`id_layered_filter`, `name`, `id_value`, `type`, `position`)
//                        VALUES ({$id_shop}, {$id_category}, {$id_value}, '{$type}', {$position})";
//            Db::getInstance()->execute($query_insert);

            foreach ($filter_config->shop_list as $id_shop)
            {
                $result = Db::getInstance()->execute("INSERT INTO `"._DB_PREFIX_."layered_filter_shop` VALUES({$id_layered_filter}, {$id_shop})");

                foreach ($filter_config->categories as $id_category)
                {
                    foreach ($filter_config->filters as $position => $filter)
                    {
                        $type = $filter->type;
                        $id_value = $filter->value;

                        if ( ! $id_value ) {
                            $id_value = 'NULL';
                        }

                        $query_insert = "
                        INSERT INTO `"._DB_PREFIX_."layered_category` 
                            (`id_shop`, `id_category`, `id_value`, `type`, `position`) 
                        VALUES ({$id_shop}, {$id_category}, {$id_value}, '{$type}', {$position})";
                        Db::getInstance()->execute($query_insert);
                    }
                }
            }
        }






    }

    protected function getFilters($id_shop)
    {
        $sql = '
            SELECT *
            FROM '._DB_PREFIX_.'category_lang AS category_lang
            RIGHT JOIN '._DB_PREFIX_.'category_shop AS category_shop ON (category_shop.id_shop = category_lang.id_shop)
            JOIN '._DB_PREFIX_.'category AS category ON (category.id_category = category_lang.id_category)
            WHERE category_lang.id_shop = '.(int)$id_shop.'
            AND NOT category.is_root_category
            AND category.active';

        $filters = Db::getInstance()->executeS($sql);

        return $filters;
    }

    protected function getSerializedFilters($filter_config) {
        $categories = [];

        foreach ($filter_config->categories as $id_category)
        {
            $categories[] = $id_category;
        }

        $shop_list = [];

        foreach ($filter_config->shop_list as $id_shop)
        {
            $shop_list[] = $id_shop;
        }

        $filters = [];

        foreach ($filter_config->filters as $postion => $filter)
        {
            $value = $filter->value;
            $type = '';
            $type_shortkey = $filter->type;
            switch ($type_shortkey) {
                case 'price':
                    $type = "price";
                    $value = 'slider';
                    break;
                case 'category':
                    $type = "subcategories";
                    break;
                case 'id_attribute_group':
                    $type = "ag";
                    break;
                case 'id_feature':
                    $type = "feat";
                    break;
                case 'quantity':
                    $type = "stock";
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
            $type_shortkey = "layered_selection_{$type}_{$value}";

            $filters[$type_shortkey]['filter_type'] = 0;
            $filters[$type_shortkey]['filter_show_limit'] = 0;
        }

        $filters_serialized = [
            'categories' => $categories,
            'shop_list' => $shop_list,
            'filters' => $filters
        ];

        return serialize($filters_serialized);
    }

}
