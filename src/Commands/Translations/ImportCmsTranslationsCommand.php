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

class ImportCmsTranslationsCommand extends PrestashopCommand
{

    use FilesystemTrait;

    const CONTENT_FILENAME = 'content.htm';
    const METADATA_FILENAME = 'metadata.json';

    public function __construct($name = null)
    {
        $this->filesystem = new Filesystem;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('translations:cms-import')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id_lang = (int)$this->input->getOption('id_lang');

        // Collect all the available translations from the given path
        $path = $this->input->getArgument('path');

        // Checks for path existence and readability
        $this->validatePathExistence($path);
        $this->validatePathReadability($path);

        // Get a list of the subdirectories existing in the path
        $directories = collect(
            $this->filesystem->directories($path)
        );

        // Check if at least 1 subdirectory is found
        if( 0 == $directories->count()) {
            throw new EmptyFolderException("No subdirectories found in {$path}.");
        }

        // Get the contents of the available translated files
        $directories->each(function($directory) use ($id_lang) {
            // Get the last part of the path, containing the category id
            $directory_parts = explode(DIRECTORY_SEPARATOR, $directory);
            $id_cms = end($directory_parts);

            // Ensure the category ID is an integer
            if( ! preg_match('/^\d+$/', $id_cms) ) {
                throw new InvalidFolderException("{$directory} is not a valid path for a cms (ID missing in path).");
            }

            $id_cms = (int)$id_cms;

            // Get the translated files in this path
            $translations = collect(
                $this->filesystem->files($directory)
            );

            // Collect a set with the translated fields for this category
            $translated_data = [];

            $translations->each(function($translation) use (&$translated_data) {
                // Get the filename for this translation file
                $filename = $this->filesystem->basename($translation);
                $contents = $this->filesystem->get($translation);

                $data = [];

                // Determine the filetype
                if( self::METADATA_FILENAME == $filename ) {
                    // Get metadata from the file
                    $contents = json_decode($contents);
                    $data = [
                        'meta_title' => pSQL($contents->title),
                        'meta_description' => pSQL($contents->description),
                        'meta_keywords' => pSQL($contents->keywords),
                        'link_rewrite' => $contents->friendly_url,
                    ];
                } elseif( self::CONTENT_FILENAME == $filename ) {
                    // Replace weird non-breaking space with proper HTML-encoded version
                    $contents = preg_replace('/\xA0/', ' ', $contents);

                    $data['content'] = pSQL($contents, true);
                }

                $translated_data = array_merge($translated_data, $data);
            });

            // Insert/update the translation record in the database with translated data
            Db::getInstance()->update(
                'cms_lang',
                $translated_data,
                "`id_lang` = {$id_lang} AND `id_cms` = {$id_cms}",
                1
            );
        });

    }
}
