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
 * "vendor/bin/laminas-cli.bat" mvc:sitemap --properties=<property1> --properties=<property2> --module=<moduleName> <name>
 */
class SitemapCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:sitemap';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new sitemap.')
            ->setHelp('This command allows you to create a sitemap')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the sitemap.')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a sitemap');
        
        $moduleName = $this->getModuleName($input, $output, 'sitemap');
        
        $name = ucfirst(rtrim($input->getArgument('name'), 's')).'Controller';
        $properties = $input->getOption('properties');
                
        $this->createStaticController($moduleName, 'Sitemap', 'SitemapController.php', $section2);
        $this->createStaticView($moduleName, 'Sitemap/View', 'index.phtml', $section2);
        
        $section2->writeln('Done creating new sitemap.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
}