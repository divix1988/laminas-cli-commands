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
 * "vendor/bin/laminas-cli.bat" mvc:crud_view --module=<moduleName> <controllerName> <name>
 */
class CrudViewCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:crud_view';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new CRUD view.')
            ->setHelp('This command allows you to create a CRUD view')
            ->addArgument('controller', InputArgument::REQUIRED, 'The name of the related controller.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the view.')
        
            ->addOption('name_singular', null, InputOption::VALUE_OPTIONAL, 'The singular name of the component.')
            ->addOption('name_plural', null, InputOption::VALUE_OPTIONAL, 'The plural name of the component.')
            ->addOption('columns', null, InputOption::VALUE_OPTIONAL, 'The columns of the component.');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a view');
        
        $moduleName = $this->getModuleName($input, $output, 'view');
        
        $controllerName = $input->getArgument('controller');
        $name = $input->getArgument('name');
        
        $contents = @file_get_contents(__DIR__.'/Templates/Crud/View/'.$name.'.phtml');
        
        if (empty($contents)) {
            throw new \Exception('Unsupported view type for CRUD view command.');
        }
        $contents = str_replace("%name_singular_lower%", lcfirst($input->getOption('name_singular')), $contents);
        $contents = str_replace("%name_singular_upper%", ucfirst($input->getOption('name_singular')), $contents);
        $contents = str_replace("%name_plural_lower%", lcfirst($input->getOption('name_plural')), $contents);
        $contents = str_replace("%name_plural_upper%", ucfirst($input->getOption('name_plural')), $contents);
        $columnsPhp = '';
        
        if (!empty($input->getOption('columns')) && $name != 'index') {
            foreach ($input->getOption('columns') as $column) {
                $columnsPhp .= 
    '        echo $this->formRow($'.lcfirst($input->getOption('name_singular')).'Form->get(\''.$column.'\'));'.PHP_EOL;
            }
            $contents = str_replace("%form_elements%", $columnsPhp, $contents);
        }
        $indexColumnsTh = '';
        $indexColumnsTd = '';
        
        if (!empty($input->getOption('columns')) && $name == 'index') {
            foreach ($input->getOption('columns') as $column) {
                $indexColumnsTh .= 
    '        <th>'.ucfirst($column).'</th>'.PHP_EOL;
                $indexColumnsTd .= 
    '            <td><?= $'.lcfirst($input->getOption('name_singular')).'->get'.ucfirst($column).'() ?></td>'.PHP_EOL;
            }
            $contents = str_replace("%table_columns_headings%", $indexColumnsTh, $contents);
            $contents = str_replace("%table_columns%", $indexColumnsTd, $contents);
        }
        
        if ($name == 'index') {
            $this->createPaginationView($moduleName, $controllerName, $section2);
        }
       
        $section1->writeln(PHP_EOL.$contents.PHP_EOL);

        if ($this->isJsonMode()) {
            $code = (json_encode([$name.'.phtml' => $contents]));
            $section2->writeln($code);
        } else {
            $this->storeViewContents($name.'.phtml', $moduleName, $controllerName, $contents);
        }
        $section2->writeln('Done creating new view.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
    protected function createPaginationView($moduleName, $controllerName, $section2)
    {
        $pagination = file_get_contents(__DIR__.'/Templates/Crud/View/pagination.phtml');
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['pagination.phtml' => $pagination]));
            $section2->writeln($code);
        } else {
            $this->storeViewContents('pagination.phtml', $moduleName, $controllerName, $pagination);
        }
    }
}