<?php

namespace Divix\Laminas\Cli;

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
 * "vendor/bin/laminas-cli.bat" mvc:rowset --properties=<property1> --properties=<property2> --module=<moduleName> <name>
 */
class RowsetCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:rowset';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new rowset.')
            ->setHelp('This command allows you to create a MVC rowset')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the rowset.')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a rowset');
        
        $moduleName = $this->getModuleName($input, $output, 'rowset');
        
        $name = ucfirst($input->getArgument('name'));
        $properties = $input->getOption('properties');
        
        $rowset = new ClassGenerator();
        $rowset->setName($name)
            ->setNamespaceName($moduleName . '\Model');
       
        
        if (!empty($properties)) {
            foreach ($properties as $property) {
                $rowset->addProperty($property);
                $rowset->addMethod(
                    'get' . ucfirst($property),
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    'return $this->'.$property.';'
                );
                $rowset->addMethod(
                    'set' . ucfirst($property),
                    ['value'],
                    MethodGenerator::FLAG_PUBLIC,
'$this->'.$property.' = $value;
return $this;'
                );
            }
        }
        $section2->writeln($rowset->generate());
        $this->storeRowsetContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$rowset->generate());
        $section2->writeln('Done creating new rowset.');

        return 0;
    }
}