<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Usage:
 * 
 * "vendor/bin/laminas.bat" mvc:crud_config --module=<moduleName> <name>
 */
class CrudConfigCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:crud_config';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new CRUD config.')
            ->setHelp('This command allows you to create a CRUD config')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the config.')
        
            ->addOption('name_singular', null, InputOption::VALUE_OPTIONAL, 'The singular name of the component.')
            ->addOption('name_plural', null, InputOption::VALUE_OPTIONAL, 'The plural name of the component.');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a config');
        
        $moduleName = $this->getModuleName($input, $output, 'view');
        $name = $input->getArgument('name');
        
        $contents = @file_get_contents(__DIR__.'/Templates/Crud/Config/'.$name.'.php');
        
        if (empty($contents)) {
            throw new \Exception('invalid config filename');
        }
        $contents = str_replace("%name_singular_lower%", lcfirst($input->getOption('name_singular')), $contents);
        $contents = str_replace("%name_singular_upper%", ucfirst($input->getOption('name_singular')), $contents);
        $contents = str_replace("%name_plural_lower%", lcfirst($input->getOption('name_plural')), $contents);
        $contents = str_replace("%name_plural_lower_all%", strtolower($input->getOption('name_plural')), $contents);
        $contents = str_replace("%name_plural_upper%", ucfirst($input->getOption('name_plural')), $contents);
        $contents = str_replace("%module_name%", $moduleName, $contents);
        $contents = str_replace("%name_plural_dashed%", $this->convertCamelCaseToDashes($input->getOption('name_plural')), $contents);
        $contents = str_replace("%module_name_plural_dashed%", $this->convertCamelCaseToDashes($moduleName), $contents);
       
        $section2->writeln(PHP_EOL.$contents.PHP_EOL);
        
        $this->storeConfigContents($name.'.php', $moduleName, $contents);
        $this->injectNewConfigToModuleFile($moduleName, $name);
        $section1->writeln('Done creating new config.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
    protected function createPaginationView($moduleName, $controllerName)
    {
        $pagination = file_get_contents(__DIR__.'/Templates/Crud/View/pagination.phtml');
        
        $this->storeViewContents('pagination.phtml', $moduleName, $controllerName, $pagination);
    }
}