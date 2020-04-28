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
 * "vendor/bin/laminas-cli.bat" mvc:model --properties=<property1> --properties=<property2> --module=<moduleName> <name>
 */
class ModelCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:model';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new model with CRUD methods.')
            ->setHelp('This command allows you to create a MVC model')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model.')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a model');
        
        $moduleName = $this->getModuleName($input, $output, 'model');
        
        $this->createAbstractModel($moduleName);
        
        $name = ucfirst($input->getArgument('name'));
        $properties = $input->getOption('properties');
        $generatedGetByFilters = '';
        $generatedPatchFilters = '';
        
        $model = new ClassGenerator();
        $model->setName($name)
            ->setNamespaceName($moduleName . '\Model')
            ->setExtendedClass($moduleName. '\Model\AbstractTable')
            ->addProperty('resultsPerPage', 10, PropertyGenerator::FLAG_PROTECTED);
       
        
        if (!empty($properties)) {
            foreach ($properties as $property) {
                
                if ($property === 'id') {
                    $model->addMethod(
                        'getById',
                        ['id'],
                        MethodGenerator::FLAG_PUBLIC,
                            '$id = (int) $id;'.PHP_EOL.
                            '$row = $this->getBy([\'id\' => $id]);'.PHP_EOL.PHP_EOL.

                            'if (!$row) {'.PHP_EOL.
                            '    throw new \Exception(\''.$name.' not found with id: \'.$id);'.PHP_EOL.
                            '}'.PHP_EOL.
                            'return $row;'
                    );
                } else {
                    $generatedGetByFilters .= 'if (isset($params[\''.$property.'\'])) {'.PHP_EOL.
                        '    $select->where([\''.$property.'\' => $params[\''.$property.'\']]);'.PHP_EOL.
                        '}'.PHP_EOL.PHP_EOL;
                    $generatedPatchFilters .= 'if (!empty($data[\''.$property.'\'])) {'.PHP_EOL.
                        '    $passedData[\''.$property.'\'] = $data[\''.$property.'\'];'.PHP_EOL.
                        '}'.PHP_EOL.PHP_EOL;
                }
            }
        }
        
        //add getById()
        $model->addMethod(
            'getById',
            [['name' => 'id']],
            MethodGenerator::FLAG_PUBLIC,
                'return $this->getBy([\'id\' => $id]);'
        );
        
        //add getBy()
        $model->addMethod(
           'getBy',
           [['name' => 'params', 'type' => 'array', 'defaultvalue' => []]],
           MethodGenerator::FLAG_PUBLIC,
'$select = $this->tableGateway->getSql()->select();

if (isset($params[\'id\'])) {
    $select->where([\'id\' => $params[\'id\']]);
    $params[\'limit\'] = 1;
}

'.$generatedGetByFilters.'

if (isset($params[\'limit\'])) {
    $select->limit($params[\'limit\']);
}

if (!isset($params[\'page\'])) {
    $params[\'page\'] = 0;
}

$result = (isset($params[\'limit\']) && $params[\'limit\'] == 1)
    ? $this->fetchRow($select)
    : $this->fetchAll($select, [\'limit\' => $this->resultsPerPage, \'page\' => $params[\'page\']]);

return $result;'
        );
        
        //add patch()
        $model->addMethod(
           'patch',
           [
               ['name' => 'id', 'type' => 'int'],
               ['name' => 'data', 'type' => 'array'],
           ],
           MethodGenerator::FLAG_PUBLIC,
'if (empty($data)) {
    throw new \Exception(\'missing data to update\');
}
$passedData = [];

'.$generatedPatchFilters.'
$this->tableGateway->update($passedData, [\'id\' => $id]);'
        );
        
        //add save()
        $model->addMethod(
            'save',
            [['name' => 'rowset', 'type' => 'Rowset\\'.$name]],
            MethodGenerator::FLAG_PUBLIC,
                'return parent::saveRow($rowset);'
        );
        
        //add delete()
        $model->addMethod(
            'delete',
            [['name' => 'id']],
            MethodGenerator::FLAG_PUBLIC,
'if (empty($id)) {
    throw new \Exception(\'missing '.$name.' id to delete\');
}
parent::deleteRow($id);'
        );
        
        //$section2->writeln($model->generate());
        $this->storeModelContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$model->generate());
        $section1->writeln('Done creating new model.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
    protected function createAbstractModel($moduleName)
    {
        $this->storeModelContents('AbstractTable.php', $moduleName, null, 'AbstractTable.php');
    }
}