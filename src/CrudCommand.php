<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\PropertyGenerator;

/**
 * Usage:
 * 
 * "vendor/bin/laminas.bat" mvc:crud --properties=<property1> --properties=<property2> --module=<moduleName> <name>
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
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        
        $name = ucfirst($input->getArgument('name'));
        $singularName = rtrim($name, 's');
        $properties = $this->getPropertiesArray($input);
        $generatedGetByFilters = '';
        $generatedPatchFilters = '';
        
        $section1->writeln('');
        $section1->writeln('Start creating a CRUD with properties: '.join($properties, ', '));
        
        $moduleName = $this->getModuleName($input, $output, 'model');
        
        $this->createAbstractModel($moduleName);
        
        $model = new ClassGenerator();
        $model->setName($name.'Table')
            ->setNamespaceName($moduleName . '\Model')
            ->setExtendedClass($moduleName. '\Model\AbstractTable')
            ->addProperty('resultsPerPage', 10, PropertyGenerator::FLAG_PROTECTED);
       
        
        if (!empty($properties)) {
            foreach ($properties as $property) {
                
            }
        }
        
        $section1->writeln('Start creating new Controller: Controller/'.$name.'Controller.php');
        $this->generateCrudController($moduleName, $name, $output);
        $section1->writeln('End creating new Controller.');
        
        $section1->writeln('Start creating new Model: Model/'.$name.'Table.php');
        $this->generateModel($moduleName, $name, $output, $properties);
        $section1->writeln('End creating new Model.');
        
        $section1->writeln('Start creating new Rowset: Model/Rowset/'.$singularName.'.php');
        $this->generateRowset($moduleName, $name, $output, $properties);
        $section1->writeln('End creating new Rowset.');
        
        $section1->writeln('Start creating new Form: Form/'.$singularName.'Form.php');
        $this->generateForm($moduleName, $name, $output, array_merge($properties, ['id']));
        $section1->writeln('End creating new Form.');
        
        $section1->writeln('Start creating Views for: index.phtml, create.phtml, update.phtml and delete.phtml.');
        $this->generateCrudView(
            $moduleName, 
            $name, 
            $output,
            'add',
            [
                'name_singular' => rtrim($name, 's'),
                'name_plural' => $name,
                'columns' => $properties
            ]
        );
        $this->generateCrudView(
            $moduleName, 
            $name, 
            $output,
            'delete',
            [
                'name_singular' => rtrim($name, 's'),
                'name_plural' => $name
            ]
        );
        $this->generateCrudView(
            $moduleName, 
            $name, 
            $output,
            'edit',
            [
                'name_singular' => rtrim($name, 's'),
                'name_plural' => $name,
                'columns' => array_merge($properties, ['id'])
            ]
        );
        $this->generateCrudView(
            $moduleName, 
            $name, 
            $output,
            'index',
            [
                'name_singular' => rtrim($name, 's'),
                'name_plural' => $name,
                'columns' => $properties
            ]
        );
        $section1->writeln('End creating new Views.');
        
        $section1->writeln('Start creating new Config: config/generated.crud.'.strtolower($name).'.php');
        $this->generateConfig($moduleName, $name, $output, 'generated.crud.'.strtolower($name));
        $section1->writeln('End creating new Config.');
        
        $section1->writeln('Start creating SQL script: sql/crud_'.$name.'.sql');
        $this->createSql($moduleName, $name, $properties, $section2);
        $section1->writeln('End creating SQL script.');
        
        //$section2->writeln($model->generate());
        //$this->storeModelContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$model->generate());
        $section1->writeln('Done creating new CRUD.');

        return 0;
    }
    
    protected function generateCrudController($moduleName, $name, OutputInterface $output)
    {
        $command = $this->getApplication()->find('mvc:crud_controller');

        $arguments = [
            'command' => 'mvc:crud_controller',
            'name' => $name,
            '--actions' => ['add', 'edit', 'delete'],
            '--module' => $moduleName,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }
    
    
    protected function generateForm($moduleName, $name, OutputInterface $output, array $properties)
    {
        $command = $this->getApplication()->find('mvc:form');

        $arguments = [
            'command' => 'mvc:form',
            'name' => $name,
            '--module' => $moduleName,
            '--properties' => $properties,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }
    
    protected function generateCrudView($moduleName, $name, OutputInterface $output, $viewType, array $options = [])
    {
        $command = $this->getApplication()->find('mvc:crud_view');

        $arguments = [
            'command' => 'mvc:crud_view',
            'name' => $viewType,
            'controller' => strtolower($name),
            '--module' => $moduleName,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];
        
        foreach ($options as $key => $value) {
            $arguments['--'.$key] = $value;
        }

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }
    
    protected function generateConfig($moduleName, $name, OutputInterface $output, $configName)
    {
        $command = $this->getApplication()->find('mvc:crud_config');

        $arguments = [
            'command' => 'mvc:crud_config',
            'name' => $configName,
            '--module' => $moduleName,
            '--name_singular' => rtrim($name, 's'),
            '--name_plural' => $name,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }


    protected function createAbstractModel($moduleName)
    {
        $this->storeModelContents('AbstractTable.php', $moduleName, null, 'AbstractTable.php');
    }
    
    protected function createSql($moduleName, $name, $properties, $section2)
    {
        $name = strtolower($name);
        $contents = 'CREATE TABLE `'.$name.'` (
   `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
';
        foreach ($properties as $property) {
            $contents .= '    `'.trim($property).'` varchar(250) NOT NULL,'.PHP_EOL;
        }
        $contents = rtrim(trim($contents), ',').PHP_EOL;
        $contents .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['crud_'.$name.'.sql' => $contents]));
            $section2->writeln($code);
        }
        
        $this->storeSqlContents('crud_'.$name.'.sql', $moduleName, $contents);
    }
}