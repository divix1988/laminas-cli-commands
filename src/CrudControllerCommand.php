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
 * "vendor/bin/laminas-cli.bat" mvc:crud_controller --actions=<action1> --actions=<action2> --module=<moduleName> <name>
 */
class CrudControllerCommand extends ControllerCommand
{
    protected static $defaultName = 'mvc:crud_controller';

    protected function configure()
    {
        parent::configure();
        
        $this
            ->setDescription('Creates a new CRUD controller.')
            ->setHelp('This command allows you to create a CRUD controller');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();

        $moduleName = $this->getModuleName($input, $output, 'controller');
        
        $name = lcfirst($input->getArgument('name'));
        $nameSingularUpper = ucfirst(rtrim($name, 's'));
        $nameSingularLower = lcfirst(rtrim($name, 's'));
        $controllerName = ucfirst($input->getArgument('name')) . 'Controller';
        $actions = $input->getOption('actions');
        
        $controller = $this->getControllerObject($controllerName, $moduleName, $actions);
        $controller->addUse($moduleName.'\Model\\'.ucfirst($name).'Table');
        $controller->addUse($moduleName.'\Model\\Rowset\\'.$nameSingularUpper);
        $controller->addUse($moduleName.'\Form\\'.$nameSingularUpper.'Form');
        $controller->addProperty($name.'Table', null, PropertyGenerator::FLAG_PROTECTED);
        
        $controller->addMethod(
            '__construct',
            [['type' => $moduleName.'\Model\\'.ucfirst($name).'Table', 'name' => $name.'Table']],
            MethodGenerator::FLAG_PUBLIC,
'$this->'.$name.'Table = $'.$name.'Table;'        
            );
        
        $controller->getMethod('indexAction')
            ->setBody(
'$view = new ViewModel();
$rows = $this->'.$name.'Table->getBy([\'page\' => $this->params()->fromRoute(\'page\')]);

$view->setVariable(\''.rtrim($name, 's').'Rows\', $rows);

return $view;'
            );
        
        $controller->getMethod('addAction')
            ->setBody(
'$request = $this->getRequest();
$'.$name.'Form = new '.$nameSingularUpper.'Form();
$'.$name.'Form->get(\'submit\')->setValue(\'Add\');

if (!$request->isPost()) {
    return [\''.$nameSingularLower.'Form\' => $'.$name.'Form];
}
$'.$name.'Model = new '.$nameSingularUpper.'();
$'.$name.'Form->setInputFilter($'.$name.'Model->getInputFilter(false));
$'.$name.'Form->setData($request->getPost());

if (!$'.$name.'Form->isValid()) {
    print_r($'.$name.'Form->getMessages());
    return [\''.$nameSingularLower.'Form\' => $'.$name.'Form];
}
$'.$name.'Model->exchangeArray($'.$name.'Form->getData());
$this->'.$name.'Table->save($'.$name.'Model);

$this->redirect()->toRoute(\''.$name.'\');'
            );
        
        
        $controller->getMethod('editAction')
            ->setBody(
'$view = new ViewModel();
$'.$name.'Id = (int) $this->params()->fromRoute(\'id\');
$view->setVariable(\''.$nameSingularLower.'Id\', $'.$name.'Id);
if ($'.$name.'Id == 0) {
    return $this->redirect()->toRoute(\''.$name.'\', [\'action\' => \'add\']);
}
// get user data; if it doesn’t exists, then redirect back to the index
try {
    $'.$name.'Row = $this->'.$name.'Table->getById($'.$name.'Id);
} catch (\Exception $e) {
    return $this->redirect()->toRoute(\''.$name.'\', [\'action\' => \'index\']);
}
$'.$name.'Form = new '.$nameSingularUpper.'Form();
$'.$name.'Form->bind($'.$name.'Row);

$'.$name.'Form->get(\'submit\')->setAttribute(\'value\', \'Save\');
$request = $this->getRequest();
$view->setVariable(\''.$nameSingularLower.'Form\', $'.$name.'Form);

if (!$request->isPost()) {
    return $view;
}
$'.$name.'Form->setInputFilter($'.$name.'Row->getInputFilter());
$'.$name.'Form->setData($request->getPost());

if (!$'.$name.'Form->isValid()) {
    return $view;
}
$this->'.$name.'Table->save($'.$name.'Row);
// data saved, redirect to the users list page
return $this->redirect()->toRoute(\''.$name.'\', [\'action\' => \'index\']);'
          );
        
        
        $controller->getMethod('deleteAction')
            ->setBody(
'$'.$nameSingularLower.'Id = (int) $this->params()->fromRoute(\'id\');

if (empty($'.$nameSingularLower.'Id)) {
    return $this->redirect()->toRoute(\''.$name.'\');
}
$request = $this->getRequest();

if ($request->isPost()) {
    $del = $request->getPost(\'del\', \'Cancel\');

    if ($del == \'Delete\') {
        $'.$nameSingularLower.'Id = (int) $request->getPost(\'id\');
        $this->'.$name.'Table->delete($'.$nameSingularLower.'Id);
    }
    // redirect to the users list
    return $this->redirect()->toRoute(\''.$name.'\');
}
return [
    \'id\' => $'.$nameSingularLower.'Id,
    \''.$nameSingularLower.'\' => $this->'.$name.'Table->getById($'.$nameSingularLower.'Id),
];'
          );
        
        $this->storeControllerContents($controllerName.'.php', $moduleName, '<?php'.PHP_EOL.$controller->generate());

        return 0;
    }
}