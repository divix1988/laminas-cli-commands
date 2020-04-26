<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\ArrayInput;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Usage:
 * 
 * "vendor/bin/laminas-cli.bat" mvc:crud --properties=<property1> --properties=<property2> --module=<moduleName> <name>
 */
class CrudCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:crud';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new whole module with CRUD methods.')
            ->setHelp('This command allows you to create a Create Update Delete module')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the CRUD.')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a CRUD');
        
        $moduleName = $this->getModuleName($input, $output, 'model');
        
        $this->createAbstractModel($moduleName);
        
        $name = ucfirst($input->getArgument('name'));
        $properties = $input->getOption('properties');
        $generatedGetByFilters = '';
        $generatedPatchFilters = '';
        
        $model = new ClassGenerator();
        $model->setName($name.'Table')
            ->setNamespaceName($moduleName . '\Model')
            ->setExtendedClass($moduleName. '\Model\AbstractTable')
            ->addProperty('resultsPerPage', 10, PropertyGenerator::FLAG_PROTECTED);
       
        
        if (!empty($properties)) {
            foreach ($properties as $property) {
                
            }
        }
        
        $section1->writeln('Start creating new Controller.');
        $this->generateController($moduleName, $name, $output);
        $section1->writeln('End creating new Controller.');
        
        //$section2->writeln($model->generate());
        //$this->storeModelContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$model->generate());
        $section1->writeln('Done creating new CRUD.');

        return 0;
    }
    
    protected function generateController($moduleName, $name, OutputInterface $output)
    {
        $command = $this->getApplication()->find('mvc:crud_controller');

        $arguments = [
            'command' => 'mvc:crud_controller2',
            'name' => $name,
            '--actions' => ['add', 'edit', 'delete'],
            '--module' => $moduleName,
            '--print_mode' => true
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }


    protected function createAbstractModel($moduleName)
    {
        $this->storeModelContents('AbstractTable.php', $moduleName, null, 'AbstractTable.php');
    }
}