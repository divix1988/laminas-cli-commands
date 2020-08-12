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
 * "vendor/bin/laminas-cli.bat" mvc:rowset --properties=<property1> --properties=<property2> --module=<moduleName> <name>
 */
class RowsetCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:rowset';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new rowset.')
            ->setHelp('This command allows you to create a MVC rowset')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the rowset.')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a rowset');
        
        $moduleName = $this->getModuleName($input, $output, 'rowset');
        
        $name = ucfirst($input->getArgument('name'));
        $properties = $input->getOption('properties');
        
        $rowset = new ClassGenerator();
        $rowset->setName($name)
            ->setNamespaceName($moduleName . '\Model\Rowset')
            ->setExtendedClass($moduleName . '\Model\Rowset\AbstractModel')
            ->setImplementedInterfaces(['\Laminas\InputFilter\InputFilterAwareInterface']);
       
        $exchangeArrayBody = 
        '       $this->id = (!empty($row[\'id\'])) ? $row[\'id\'] : null;'.PHP_EOL;
        $getArrayCopyBody = '\'id\' => $this->getId(),'.PHP_EOL;
        
        if (!array_search('id', $properties)) {
            $properties[] = 'id';
        }

        if (!empty($properties)) {
            foreach ($properties as $property) {
                $rowset->addProperty($property);
                $rowset->addMethod(
                    'get' . ucfirst($property),
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    'return $this->'.$property.';'
                );
                $rowset->addMethod(
                    'set' . ucfirst($property),
                    ['value'],
                    MethodGenerator::FLAG_PUBLIC,
'$this->'.$property.' = $value;
return $this;'
                );
                
                $exchangeArrayBody .= '$this->'.$property.' = (!empty($row[\''.$property.'\'])) ? $row[\''.$property.'\'] : null;'.PHP_EOL;
                $getArrayCopyBody .= '    \''.$property.'\' => $this->get'. ucfirst($property).'(),'.PHP_EOL;
            }
        }
        
        $getArrayCopyBody = rtrim($getArrayCopyBody, PHP_EOL);
        
        $rowset
            ->addMethod(
                'exchangeArray',
                [['name' => 'row', 'type' => 'array']],
                MethodGenerator::FLAG_PUBLIC,
                $exchangeArrayBody
            )
            ->addMethod(
                'getArrayCopy',
                [],
                MethodGenerator::FLAG_PUBLIC,
'return[
    '.$getArrayCopyBody.'
];'
            )
            ->addMethod(
                'getInputFilter',
                [],
                MethodGenerator::FLAG_PUBLIC,
'return new \Laminas\InputFilter\InputFilter();'
            )
            ->addMethod(
                'setInputFilter',
                [['name' => 'inputFilter', 'type' => '\Laminas\InputFilter\InputFilterInterface']],
                MethodGenerator::FLAG_PUBLIC,
'throw new DomainException(\'This class does not support adding of extra input filters\');'
            );
        
        $this->createAbstractRowset($moduleName, $section2);
        
        if ($this->isJsonMode()) {
            $code = (json_encode([$name.'.php' => $rowset->generate()]));
            $section2->writeln($code);
        }
        //$section2->writeln($rowset->generate());
        $this->storeRowsetContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$rowset->generate());
        $section2->writeln('Done creating new rowset.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
    protected function createAbstractRowset($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/AbstractModel.php');
        
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode(['AbstractModel.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeRowsetContents('AbstractModel.php', $moduleName, $abstractContents);
    }
}