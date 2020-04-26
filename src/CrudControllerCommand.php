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
 * "vendor/bin/laminas-cli.bat" mvc:crud_controller --actions=<action1> --actions=<action2> --module=<moduleName> <name>
 */
class CrudControllerCommand extends ControllerCommand
{
    protected static $defaultName = 'mvc:crud_controller';

    protected function configure()
    {
        parent::configure();
        
        $this
            ->setDescription('Creates a new CRUD controller.')
            ->setHelp('This command allows you to create a CRUD controller');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();
        $section2 = $output->section();

        $moduleName = $this->getModuleName($input, $output, 'controller');
        
        $name = lcfirst($input->getArgument('name'));
        $controllerName = ucfirst($input->getArgument('name')) . 'Controller';
        $actions = $input->getOption('actions');
        
        $controller = $this->getControllerObject($controllerName, $moduleName, $actions);
        $controller->addUse($moduleName.'\Model\\'.ucfirst($name).'Table');
        $controller->addProperty($name.'Table', null, PropertyGenerator::FLAG_PROTECTED);
        
        $controller->addMethod(
            '__constructor',
            [['type' => $moduleName.'\Model\\'.ucfirst($name).'Table', 'name' => $name.'Table']],
            MethodGenerator::FLAG_PUBLIC,
'$this->'.$name.'Table = $'.$name.'Table;'        
            );
        
        $controller->getMethod('indexAction')
            ->setBody(
'return [
    \'comics\' => $this->comicsTable->getBy([\'page\' => $this->params()->fromRoute(\'page\')])
];'
            );
        
        $this->storeControllerContents($controllerName.'.php', $moduleName, '<?php'.PHP_EOL.$controller->generate());

        return 0;
    }
}