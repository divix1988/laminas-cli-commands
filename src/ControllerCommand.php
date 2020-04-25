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

/**
 * Usage:
 * 
 * "vendor/bin/laminas-cli.bat" mvc:controller --actions=<action1> --actions=<action2> <name>
 */
class ControllerCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:controller';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new controller.')
            ->setHelp('This command allows you to create a MVC controller')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller.')
            ->addOption('actions', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Action names list');
            
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a controller');
        
        $moduleName = $this->getModuleName($input, $output, 'controller');
        
        $name = ucfirst($input->getArgument('name')) . 'Controller';
        $actions = $input->getOption('actions');
        $methodActions = ['index'];
        
        $controller = new ClassGenerator();
        $controller->setName($name)
            ->setNamespaceName($moduleName . '\Controller\\' . $name)
            ->setExtendedClass('Laminas\Mvc\Controller\AbstractActionController');
        
        if (!empty($actions)) {
            $methodActions = array_merge($methodActions, $actions);
            array_unique($methodActions);
        }
        
        foreach ($methodActions as $action) {
            $controller->addMethod(
                $action . 'Action'
            );
        }
        $section2->writeln($controller->generate());
        
        
        $this->storeControllerContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$controller->generate());
        $section2->writeln('Done creating new controller.');

        return 0;
    }
}