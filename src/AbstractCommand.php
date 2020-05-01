<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
    
    protected function configure()
    {
        $this->addOption('module', null, InputOption::VALUE_OPTIONAL, 'The module name of the component.');
        $this->addOption('print_mode', null, InputOption::VALUE_OPTIONAL, 'Print only the generated file and don\'t store it.');
    }
    
    protected function postExecute(InputInterface $input, OutputInterface $output, ConsoleSectionOutput &$section1, &$section2)
    {
        if ($this->getPrintMode($input)) {
            $section1->clear();
            $section2->clear();
        }
    }
    
    protected function getPrintMode($input)
    {
        $value = $input->getOption('print_mode');
        
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
    
    protected function storeControllerContents($fileName, $moduleName, $contents): void
    {
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_CONTROLLER_SRC;
        
        $this->createFoldersForDir($dir);
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeModelContents($fileName, $moduleName, $contents = null, $templateFile = null): void
    {
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_MODEL_SRC;

        $this->createFoldersForDir($dir);
        
        if (empty($contents) && isset($templateFile)) {
            $contents = file_get_contents(__DIR__.'/Templates/'.$templateFile);
        }
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeRowsetContents($fileName, $moduleName, $contents): void
    {
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_ROWSET_SRC;
        
        $this->createFoldersForDir($dir);
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeFormContents($fileName, $moduleName, $contents): void
    {
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_FORM_SRC;

        $this->createFoldersForDir($dir); 
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeConfigContents($fileName, $moduleName, $contents): void
    {
        $dir = self::MODULE_SRC.$moduleName.self::MODULE_CONFIG_SRC;

        $this->createFoldersForDir($dir); 
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function storeViewContents($fileName, $moduleName, $controllerName, $contents): void
    {
        $moduleName = strtolower($moduleName);
        $dir = self::MODULE_SRC.$moduleName.'/view/'.$controllerName.'/';
        
        $this->createFoldersForDir($dir);
        file_put_contents($dir.$fileName, $contents);
    }
    
    protected function createFoldersForDir($dir): void
    {
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
        $filePath = self::MODULE_SRC.$moduleName.self::MODULE_FILE_SRC;
        
        $contents = file_get_contents($filePath);
        
        $contents = str_replace(
            'return include __DIR__ . \'/../config/module.config.php\';',
            'return array_merge(include __DIR__ . \'/../config/module.config.php\', include __DIR__ . \'/../config/'.$name.'.php\');', 
            $contents
        );
        
        file_put_contents($filePath, $contents);
    }
}