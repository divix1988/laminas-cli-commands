<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\PropertyGenerator;

class AbstractCommand extends Command
{
    const MODULE_SRC = __DIR__.'/../../../../module/';
    const MODULE_CONTROLLER_SRC = '/src/Controller/';
    const MODULE_MODEL_SRC = '/src/Model/';
    const MODULE_FORM_SRC = '/src/Form/';
    const MODULE_FILE_SRC = '/src/Module.php';
    const MODULE_CONFIG_SRC = '/config/';
    const MODULE_ROWSET_SRC = '/src/Model/Rowset/';
    
    protected $input;
    protected $registerOtherCommands = false;
    
    public function __construct($setRegisterOtherCommands = false)
    {
        $this->registerOtherCommands = $setRegisterOtherCommands;
        parent::__construct();
    }

    protected function configure()
    {
        if ($this->registerOtherCommands) {
            $app = new \Symfony\Component\Console\Application();
            $app->addCommands([
                new \Divix\Laminas\Cli\Command\CrudControllerCommand(),
                new \Divix\Laminas\Cli\Command\CrudViewCommand(),
                new \Divix\Laminas\Cli\Command\CrudConfigCommand(),
                new \Divix\Laminas\Cli\Command\ModelCommand(),
                new \Divix\Laminas\Cli\Command\RowsetCommand(),
                new \Divix\Laminas\Cli\Command\FormCommand(),
            ]);

            $this->setApplication($app);
        }
        
        $this->addOption('module', null, InputOption::VALUE_OPTIONAL, 'The module name of the component.');
        $this->addOption('print_mode', null, InputOption::VALUE_OPTIONAL, 'Print only the generated file and don\'t store it.');
        $this->addOption('json', null, InputOption::VALUE_OPTIONAL, 'Print only output in josn and do not store the file.');
    }
    
    protected function postExecute(InputInterface $input, OutputInterface $output, OutputInterface &$section1, &$section2)
    {
        if ($this->getPrintMode($input)) {
            $section1->clear();
            //$section2->clear();
        }
    }
    
    public function setRegisterOtherCommands($value) {
        exit('calling set with: '.$value);
        $this->registerOtherCommands = $value;
    }
    
    protected function getPrintMode($input)
    {
        $value = $input->getOption('print_mode');
        
        return (!empty($value) && $value == true);
    }
    
    protected function isJsonMode($input = null)
    {
        if (empty($input)) {
            $input = $this->input;
        }
        $value = $input->getOption('json');
        
        return (!empty($value) && $value == true);
    }

    protected function getModuleName($input, $output, $componentName): string
    {
        $moduleName = $input->getOption('module');
        
        if (empty($moduleName)) {
            $helper = $this->getHelper('question');
            $question = new Question(
                'In which module you want to create a '.$componentName.'?'.PHP_EOL
            );

            $moduleName = $helper->ask($input, $output, $question);
        }
        
        return ucfirst($moduleName);
    }
    
    protected function getPropertiesArray($input, $alias = 'properties'): array
    {
        $output = $input->getOption($alias);
        
        return is_array($output) ? $output : [$output];
    }
    
    protected function storeControllerContents($fileName, $moduleName, $contents): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_CONTROLLER_SRC;
        
        $this->createFoldersForDir($dir);
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeModelContents($fileName, $moduleName, $contents = null, $templateFile = null): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_MODEL_SRC;

        $this->createFoldersForDir($dir);
        
        if (empty($contents) && isset($templateFile)) {
            $contents = file_get_contents(__DIR__.'/Templates/'.$templateFile);
        }
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeRowsetContents($fileName, $moduleName, $contents): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_ROWSET_SRC;
        
        $this->createFoldersForDir($dir);
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeFormContents($fileName, $moduleName, $contents): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_FORM_SRC;

        $this->createFoldersForDir($dir); 
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeConfigContents($fileName, $moduleName, $contents): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_CONFIG_SRC;

        $this->createFoldersForDir($dir); 
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeViewContents($fileName, $moduleName, $controllerName, $contents): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $moduleName = strtolower($moduleName);
        $dir = self::MODULE_SRC.$moduleName.'/view/'.$controllerName.'/';
        
        $this->createFoldersForDir($dir);
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function createFoldersForDir($dir): void
    {
        if ($this->isJsonMode()) {
            return;
        }
        $exploded = explode('/', $dir);
        $currentFolder = '';
        
        foreach ($exploded as $folder) {
            $currentFolder .= $folder.'/';
                    
            if (!file_exists($currentFolder)) {
                mkdir($currentFolder);
            }
        }
    }
    
    protected function convertCamelCaseToDashes($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
          $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('-', $ret);
    }
    
    protected function injectNewConfigToModuleFile($moduleName, $name)
    {
        if ($this->isJsonMode()) {
            return 'return array_merge(include __DIR__ . \'/../config/module.config.php\', include __DIR__ . \'/../config/'.$name.'.php\');';
        }
        $filePath = self::MODULE_SRC.$moduleName.self::MODULE_FILE_SRC;
        
        $contents = file_get_contents($filePath);
        
        $contents = str_replace(
            'return include __DIR__ . \'/../config/module.config.php\';',
            'return array_merge(include __DIR__ . \'/../config/module.config.php\', include __DIR__ . \'/../config/'.$name.'.php\');', 
            $contents
        );
        
        file_put_contents($filePath, $contents);
    }
    
    protected function generateModel($moduleName, $name, OutputInterface $output, array $properties)
    {
        $command = $this->getApplication()->find('mvc:model');

        $arguments = [
            'command' => 'mvc:model',
            'name' => $name.'Table',
            //'--actions' => ['create', 'update', 'delete'],
            '--module' => $moduleName,
            '--properties' => $properties,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }
    
    protected function generateRowset($moduleName, $name, OutputInterface $output, array $properties)
    {
        $command = $this->getApplication()->find('mvc:rowset');

        $arguments = [
            'command' => 'mvc:rowset',
            'name' => rtrim($name, 's'),
            '--module' => $moduleName,
            '--properties' => $properties,
            '--print_mode' => true,
            '--json' => $this->isJsonMode()
        ];

        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
    }
    
    protected function createSimplePHP($moduleName, $filename, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/AdminPanel/'.$filename);
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode([$filename => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeControllerContents($filename, $moduleName, $abstractContents);
    }
    
    protected function createStaticController($moduleName, $folder, $filename, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/'.$folder.'/'.$filename);
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode([$filename => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeControllerContents($filename, $moduleName, $abstractContents);
    }
    
    protected function createStaticConfig($moduleName, $filename, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/AdminPanel/'.$filename);
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode([$filename => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeControllerContents($filename, $moduleName, $abstractContents);
    }
    
    protected function createStaticView($moduleName, $folder, $filename, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/'.$folder.'/'.$filename);
        
        if ($this->isJsonMode()) {
            $code = (json_encode([$filename => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeViewContents($filename.'.php', $moduleName, 'admin', $abstractContents);
    }
}