<?php

namespace Flooris\Prestashop\Commands\Translations;

use Db;
use Illuminate\Filesystem\Filesystem;
use Flooris\Prestashop\Traits\FilesystemTrait;
use Symfony\Component\Console\Input\InputOption;
use Flooris\Prestashop\Commands\PrestashopCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Flooris\Prestashop\Exceptions\EmptyFolderException;
use Flooris\Prestashop\Exceptions\InvalidFolderException;

class ImportMetaTranslationsCommand extends PrestashopCommand
{

    use FilesystemTrait;

    const METADATA_FILE_REGEX = '/^(\d+)\.json/';

    public function __construct($name = null)
    {
        $this->filesystem = new Filesystem;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('translations:meta-import')
            ->addArgument(
                'path',
                InputArgument::REQUIRED
            )
            ->addOption(
                'id_lang',
                null,
                InputOption::VALUE_REQUIRED,
                'Language ID that will be inserted/updated',
                1
            )
            ->addOption(
                'id_shop',
                null,
                InputOption::VALUE_REQUIRED,
                'Shop ID for which translations will be inserted/updated',
                1
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id_lang = (int)$this->input->getOption('id_lang');
        $id_shop = (int)$this->input->getOption('id_shop');

        // Collect all the available translations from the given path
        $path = $this->input->getArgument('path');

        // Checks for path existence and readability
        $this->validatePathExistence($path);
        $this->validatePathReadability($path);

        // Get a list of the subdirectories existing in the path
        $files = collect(
            $this->filesystem->files($path)
        );

        // Check if at least 1 subdirectory is found
        if( 0 == $files->count()) {
            throw new EmptyFolderException("No subdirectories found in {$path}.");
        }

        // Get the contents of the available translated files
        $files->each(function($file) use ($id_lang, $id_shop) {
            // Get the last part of the path, containing the meta id
            $filename_parts = explode(DIRECTORY_SEPARATOR, $file);
            $filename = end($filename_parts);

            // Ensure the category ID is an integer
            if( ! preg_match(self::METADATA_FILE_REGEX, $filename, $matches) ) {
                throw new InvalidFolderException("{$file} is not a valid name for a meta translation.");
            }

            $id_meta = (int)$matches[1];
            $contents = $this->filesystem->get($file);
            $contents = json_decode($contents);

            $translated_data = [
                'title' => pSQL($contents->title, true),
                'description' => pSQL($contents->description, true),
                'keywords' => pSQL($contents->keywords, true),
                'url_rewrite' => pSQL($contents->url_rewrite, true),
            ];

            // Insert/update the translation record in the database with translated data
            Db::getInstance()->update(
                'meta_lang',
                $translated_data,
                "`id_lang` = {$id_lang} AND `id_shop` = {$id_shop} AND `id_meta` = {$id_meta}",
                1
            );
        });

    }
}
