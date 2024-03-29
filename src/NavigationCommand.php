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
 * "vendor/bin/laminas.bat" mvc:navigation --module=<moduleName> --items<item1> --items<item2> --include_controller=<boolean> <name>
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
            ->addOption('include_controller', null, InputOption::VALUE_OPTIONAL, 'Create Navigation controller');
        
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
        
        $globalPhpCode = 
'
        \''.$name.'\' => [
';

        foreach ($inputItems as $item) {
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
        $section1->writeln('Start creating layout.phtml section');
        $this->injectPhtmlCodes([
            'layout/layout.phtml' => 
'
<?= $this->navigation(\'Laminas\Navigation\\'.$name.'\')->menu()
    ->setMaxDepth(2)
    ->setPartial(\'_shared/menu\')
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

        $section1->writeln('Start creating config files: global.php, module.config.php');
        $this->injectConfigCodes([
            'autoload/global.php' => [
                ('navigation') => [
                    'identifier' => "'$name' =>",
                    'contents' => $globalPhpCode
                ],
                'service_manager/abstract_factories' => [
                    'identifier' => 'NavigationAbstractServiceFactory',
                    'contents' => 
'Laminas\Navigation\Service\NavigationAbstractServiceFactory::class,'
                ]
            ],
        ], $section2, $moduleName, 'main');

        $this->injectConfigCodes([
            'module.config.php' => [
                'controllers/factories' => [
                    'identifier' => $name.'MenuController',
                    'contents' => 
'
            Controller\\'.$name.'MenuController::class => InvokableFactory::class,
'
                ],
                /*'view_manager/template_path_stack' => 
'    
            __DIR__ . \'/../view\',
            __DIR__ . \'/../view/'.$moduleName.'/_shared\'
',*/
                'router/routes' => [
                    'identifier' => $name.'MenuController',
                    'contents' => 
'
'.$this->getRoutesCode($name, $moduleName, $inputItems).'
'
                ]
            ]
        ], $section2, $moduleName, 'module');

        $section1->writeln('Start creating new view: menu.phtml');
        $this->createStaticView($moduleName, 'Navigation/View', 'menu.phtml', $section2);
        
        if (!empty($input->getOption('include_controller'))) {
            $section1->writeln('Start creating new controller');
            $this->generateController($moduleName, $name, $output, $inputItems);
            $this->generateViews($moduleName, strtolower($name).'-menu', $output, $inputItems);
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
                         \'controller\' => Controller\\'.$name.'MenuController::class,
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
                $name, 
                $output,
                strtolower(str_replace(' ', '', $item))
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
    
    protected function generateController($moduleName, $name, OutputInterface $output, $items)
    {
        $command = $this->getApplication()->find('mvc:controller');

        $arguments = [
            'command' => 'mvc:controller',
            'name' => $name.'Menu',
            '--actions' => $items,
            '--module' => $moduleName,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }
    
}