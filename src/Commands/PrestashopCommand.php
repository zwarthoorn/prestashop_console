<?php

namespace Flooris\Prestashop\Commands;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class PrestashopCommand extends Command
{

    /**
     * @var array
     * List of directories that are expected to exist in a Prestashop installation
     */
    protected $directories_list = [
        '/config',
        '/controllers',
        '/classes',
        '/modules',
        '/themes',
        '/override'
    ];

    /** @var InputInterface $input */
    protected $input;

    /** @var OutputInterface $output */
    protected $output;

    /** @var SymfonyStyle $io */
    protected $io;

    protected $ps_basedir = '';

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Store i/o interfaces for easier access later on
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        // Attempt to get Prestashop bearings
        $ps_basedir = $this->getPrestashopBasedir();

        // Check if a valid Prestashop root folder has been found
        if( ! $ps_basedir ) {
            $this->io->error("No prestashop root found in current working directory");
            exit(1);
        }

        $this->ps_basedir = $ps_basedir;

        // Load the prestashop Core
        require_once $this->ps_basedir . '/config/config.inc.php';
    }

    protected function getPrestashopBasedir()
    {
        // Store the current working directory
        $cwd = getcwd();

        do {
            $is_prestashop_folder = $this->isPrestashopFolder($cwd);
            if( ! $is_prestashop_folder ) {
                $cwd .= '/..';
                $cwd = realpath($cwd);
            }
        } while( ! $is_prestashop_folder and $cwd != '/' );

        // Check if a valid Prestashop root folder has been found
        if( ! $is_prestashop_folder ) {
            return false;
        }

        return $cwd;
    }

    protected function isPrestashopFolder($working_directory)
    {
        $fs = new Filesystem();

        // Prepend the current working directory to all the folder checks
        $directories_to_check = [];
        foreach( $this->directories_list as $directory ) {
            $directories_to_check[] = realpath($working_directory . $directory);
        }

        // Check if the folders exist
        return $fs->exists($directories_to_check);
    }
}
