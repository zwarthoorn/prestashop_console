<?php

namespace Flooris\Prestashop;

use Db;

class PrestashopHelper
{
    /**
     * Get Filters for a Category
     *
     * @param int $id_category
     * @param int $id_shop
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function GetCategoryFilters($id_category = 1, $id_shop = 1)
    {
        $sql = "
            SELECT 
                `id_value` AS `value`, 
                `type`
            FROM `"._DB_PREFIX_."layered_category`
            WHERE   `id_shop`       = {$id_shop}
            AND     `id_category`   = {$id_category}
            ORDER BY `position`";

        $filters = Db::getInstance()->executeS($sql);

        die(json_encode($filters, JSON_PRETTY_PRINT));

        $filters = collect($filters);
        if ( ! $filters->count() ) {
            throw new \Exception("No filters found for category with ID: {$id_category}");
        }

        return $filters;
    }

    /**
     * Get Filter Configuration Profiles for a Shop
     *
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function GetFilterConfigurations()
    {
        $sql = '
            SELECT 
                `id_layered_filter`, 
                `name`, 
                `filters`,
                `n_categories`, 
                `date_add` 
            FROM '._DB_PREFIX_.'layered_filter';

        $filters = Db::getInstance()->executeS($sql);

        $filters = collect($filters);
        if ( ! $filters->count() ) {
            throw new \Exception("No filters found for category with ID: {$id_category}");
        }

        return $filters;
    }

    public static function GetFilterCategories($id_layered_filter = 0)
    {
        $sql = "
            SELECT 
                `id_layered_filter`, 
                `name`, 
                `filters`,
                `n_categories`, 
                `date_add` 
            FROM `"._DB_PREFIX_."layered_filter`
            WHERE `id_layered_filter` = {$id_layered_filter}";

        $filters = Db::getInstance()->executeS($sql);

        $filters = collect($filters);
        if ( ! $filters->count() ) {
            throw new \Exception("No filters found for id_layered_filter: {$id_layered_filter}");
        }

        return $filters;
    }

}