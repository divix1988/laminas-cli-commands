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
 * "vendor/bin/laminas-cli.bat" mvc:form --properties=<property1> --properties=<property2> --module=<moduleName> <name>
 */
class FormCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:form';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new form.')
            ->setHelp('This command allows you to create a form')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the form.')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a form');
        
        $moduleName = $this->getModuleName($input, $output, 'form');
        
        $name = ucfirst(rtrim($input->getArgument('name'), 's')).'Form';
        $properties = $input->getOption('properties');
        
        $form = new ClassGenerator();
        $form->setName($name)
            ->setNamespaceName($moduleName . '\Form')
            ->addUse('\Laminas\Form\Element')
            ->setExtendedClass('\Laminas\Form\Form')
            ->setImplementedInterfaces(['\Laminas\InputFilter\InputFilterProviderInterface']);
       
        $formElements = '';

        if (!empty($properties)) {
            foreach ($properties as $property) {
                $form->addConstant('ELEMENT_'.strtoupper($property), $property);
                $formElements .= 
'$this->add([
    \'name\' => self::ELEMENT_'.strtoupper($property).',
    \'type\' => \'text\',
    \'options\' => [
        \'label\' => \''.$property.'\'
    ],
    \'attributes\' => [
        \'required\' => true
    ]
]);'.PHP_EOL.PHP_EOL;
            }
        }
        
        $form
            ->addMethod(
                '__construct',
                [
                    ['name' => 'name', 'defaultvalue' => rtrim($input->getArgument('name'), 's').'_form'],
                    ['name' => 'params', 'type' => 'array', 'defaultvalue' => []]
                ],
                MethodGenerator::FLAG_PUBLIC,
'parent::__construct($name, $params);
$this->setAttribute(\'class\', \'styledForm\');'.PHP_EOL.PHP_EOL.$formElements.PHP_EOL.
'$this->add([
    \'name\' => \'submit\',
    \'type\' => \'submit\',
    \'attributes\' => [
        \'value\' => \'Submit\',
        \'class\' => \'btn btn-primary\'
    ]
]);'
            )
            ->addMethod(
                'getInputFilterSpecification',
                [],
                MethodGenerator::FLAG_PUBLIC,
                'return [];'
            );
        
        if ($this->isJsonMode($input)) {
            $code = (json_encode([$name.'.php' => $form->generate()]));
            $section2->writeln($code);
        }
        
        //$section2->writeln($rowset->generate());
        $this->storeFormContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$form->generate());
        $section2->writeln('Done creating new form.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
}