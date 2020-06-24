<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Usage:
 * 
 * "vendor/bin/laminas-cli.bat" mvc:navigation --module=<moduleName>
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
            ->addOption('items', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Navigation items list');
        
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
        
        if ($this->isJsonMode()) {
            $globalPhpCode = 
'
...

\'navigation\' => [
    \''.$name.'\' => [
';
            
            foreach ($inputItems as $item) {
                $globalPhpCode .= 
'        [
            \'label\' => \''.$item.'\',
            \'route\' => \''. str_replace(' ', '', strtolower($item)).'\',
            \'priority\' => \'1.0\'
        ],
';
            }
            
            $globalPhpCode .= 
'    ]
]

...
';
            
            $code = (json_encode([
                'global.php' => $globalPhpCode,
                'layout.phtml' => 
'
...

<?= $this->navigation(\'Laminas\Navigation\Default\')->menu()
    ->setMaxDepth(2)
    ->setPartial(\''.$moduleName.'/_shared/menu\')
    ->setRenderInvisible(false)
    ->renderPartialWithParams(
        [
            \'user\' => isset($this->user) ? $this->user : null
        ]
    )
?>

...'
            ]));
            
            $this->createStaticView($moduleName, 'Navigation/View', 'menu.phtml', $section2);
            
            $section2->writeln($code);
        }


        $section2->writeln('Done creating navigation.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
}