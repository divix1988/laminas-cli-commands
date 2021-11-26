<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Usage:
 * 
 * "vendor/bin/laminas.bat" mvc:navigation --module=<moduleName> --items<item1> --items<item2> --include_controllers=<boolean> <name>
 */
class NavigationCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:navigation';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new navigation feature.')
            ->setHelp('This command allows you to create a MVC navigation')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the navigation.')
            ->addOption('items', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Navigation items list')
            ->addOption('include_controllers', null, InputOption::VALUE_OPTIONAL, 'Create Navigation controllers');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating navigation');
        
        $moduleName = $this->getModuleName($input, $output, 'primary');
        $name = $input->getArgument('name');
        $inputItems = $this->getPropertiesArray($input, 'items');
        $controllersConfig = '';
        
        $globalPhpCode = 
'
            \''.$name.'\' => [
';

        foreach ($inputItems as $item) {
            if (!empty($input->getOption('include_controllers'))) {
                $controllersConfig .= 
'        Controller\\'.str_replace(' ', '', ucfirst($item)).'Controller::class => InvokableFactory::class,'.PHP_EOL;
            }
            $globalPhpCode .= 
'            [
                \'label\' => \''.$item.'\',
                \'route\' => \''. str_replace(' ', '', strtolower($item)).'\',
                \'priority\' => \'1.0\'
            ],
';
        }

        $globalPhpCode .= 
'       ],
';
        $this->injectPhtmlCodes([
            'layout/layout.phtml' => 
'
<?= $this->navigation(\'Laminas\Navigation\\'.$name.'\')->menu()
    ->setMaxDepth(2)
    ->setPartial(\''.strtolower($moduleName).'//_shared/menu\')
    ->setRenderInvisible(false)
    ->renderPartialWithParams(
        [
            \'user\' => isset($this->user) ? $this->user : null
        ]
    )
?>
',
        ],
        $section2,
        $moduleName
    );

        $this->injectConfigCodes([
            'autoload/global.php' => [
                ('navigation/'.$name) => $globalPhpCode,
                'service_manager/abstract_factories' => 
'Laminas\Navigation\Service\NavigationAbstractServiceFactory::class,'
            ],
        ], $section2, $moduleName, 'main');

        $this->injectConfigCodes([
            'module.config.php' => [
                'controllers' =>
'
            '.$controllersConfig.'
',
                'template_path_stack' => [
                    'identifier' => 'shared',
                    'contents' =>
'    __DIR__ . \'/../view/'.$moduleName.'/_shared\'
'
                ],
                'routes' => [
                    'identifier' => "'".$inputItems[1]."' => ",
                    'is_alias_unique' => true,
                    'contents' =>
'
'.$this->getRoutesCode($name, $moduleName, $inputItems).'
'
                ]
            ]
        ], $section2, $moduleName, 'module');

        $this->createMenuView($moduleName, $section2);
        $section2->writeln('Done creating navigation.');
        
        if (!empty($input->getOption('include_controllers'))) {
            $this->generateControllers($moduleName, $name, $output, $inputItems);
            $this->generateViews($moduleName, $name, $output, $inputItems);
        }

        $section2->writeln('Done creating navigation.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
    protected function getRoutesCode($name, $moduleName, $inputItems)
    {
        $out = '';
        
        foreach ($inputItems as $item) {
            $lowerItem = str_replace(' ', '', trim(strtolower($item)));
            $out .= 
'            \''.$lowerItem.'\' => [
                 \'type\'    => \Laminas\Router\Http\Literal::class,
                 \'options\' => [
                     \'route\'    => \'/'.$lowerItem.'\',
                     \'defaults\' => [
                         \'controller\' => Controller\\'.$item.'Controller::class,
                         \'action\'     => \''.$lowerItem.'\',
                     ],
                 ],
            ],'.PHP_EOL;
        }
        
        return $out;
    }
    
    protected function generateViews($moduleName, $name, OutputInterface $output, $inputItems)
    {
        
        foreach ($inputItems as $item) {
            $this->generateView(
                $moduleName, 
                $item, 
                $output,
                strtolower(str_replace(' ', '', 'index'))
            );
        }

    }
    
    protected function generateView($moduleName, $name, OutputInterface $output, $viewName, array $options = [])
    {
        $command = $this->getApplication()->find('mvc:view');

        $arguments = [
            'command' => 'mvc:view',
            'name' => $viewName,
            'controller' => $name,
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
    
    protected function generateControllers($moduleName, $name, OutputInterface $output, $items)
    {
        $command = $this->getApplication()->find('mvc:controller');

        foreach ($items as $item){
            $arguments = [
                'command' => 'mvc:controller',
                'name' => $item,
                '--actions' => [],
                '--module' => $moduleName,
                '--print_mode' => true,
                '--json' => $this->isJsonMode()
            ];

            $greetInput = new ArrayInput($arguments);
            $command->run($greetInput, $output);
        }
    }
    
    protected function createMenuView($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/Navigation/View/menu.phtml');
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['_shared/menu.phtml' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeViewContents('menu.phtml', $moduleName, '_shared', $abstractContents);
    }
    
}