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
        $section1 = $output->section();
        $section2 = $output->section();

        $moduleName = $this->getModuleName($input, $output, 'controller');
        
        $name = lcfirst($input->getArgument('name'));
        $controllerName = ucfirst($input->getArgument('name')) . 'Controller';
        $actions = $input->getOption('actions');
        
        $controller = $this->getControllerObject($controllerName, $moduleName, $actions);
        $controller->addUse($moduleName.'\Model\\'.ucfirst($name).'Table');
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
        
        $controller->getMethod('createAction')
            ->setBody(
'$request = $this->getRequest();
$'.$name.'Form = new '.$name.'Form();
$'.$name.'Form->get(\'submit\')->setValue(\'Add\');

if (!$request->isPost()) {
    return [\''.$name.'Form\' => $'.$name.'Form];
}
$'.$name.'Model = new '.$name.'();
$'.$name.'Form->setInputFilter($'.$name.'Model->getInputFilter());
$'.$name.'Form->setData($request->getPost());

if (!$'.$name.'Form->isValid()) {
    print_r($'.$name.'Form->getMessages());
    return [\''.$name.'Form\' => $'.$name.'Form];
}
$'.$name.'Model->exchangeArray($'.$name.'Form->getData());
$this->'.$name.'Table->save($'.$name.'Model);

$this->redirect()->toRoute(\''.$name.'\');'
            );
        
        
        $controller->getMethod('updateAction')
            ->setBody(
'$view = new ViewModel();
$userId = (int) $this->params()->fromRoute(\'id\');
$view->setVariable(\'userId\', $userId);
if ($userId == 0) {
    return $this->redirect()->toRoute(\'users\', [\'action\' => \'add\']);
}
// get user data; if it doesn’t exists, then redirect back to the index
try {
    $userRow = $this->usersTable->getById($userId);
} catch (\Exception $e) {
    return $this->redirect()->toRoute(\'users\', [\'action\' => \'index\']);
}
$userForm = new UserForm();
$userForm->bind($userRow);

$userForm->get(\'submit\')->setAttribute(\'value\', \'Save\');
$request = $this->getRequest();
$view->setVariable(\'userForm\', $userForm);

if (!$request->isPost()) {
    return $view;
}
$userForm->setInputFilter($userRow->getInputFilter());
$userForm->setData($request->getPost());

if (!$userForm->isValid()) {
    return $view;
}
$this->usersTable->save($userRow);
// data saved, redirect to the users list page
return $this->redirect()->toRoute(\'users\', [\'action\' => \'index\']);'
          );
        
        
        $controller->getMethod('deleteAction')
            ->setBody(
'$userId = (int) $this->params()->fromRoute(\'id\');

if (empty($userId)) {
    return $this->redirect()->toRoute(\'users\');
}
$request = $this->getRequest();

if ($request->isPost()) {
    $del = $request->getPost(\'del\', \'Cancel\');

    if ($del == \'Delete\') {
        $userId = (int) $request->getPost(\'id\');
        $this->usersTable->delete($userId);
    }
    // redirect to the users list
    return $this->redirect()->toRoute(\'users\');
}
return [
    \'id\' => $userId,
    \'user\' => $this->usersTable->getById($userId),
];'
          );
        
        $this->storeControllerContents($controllerName.'.php', $moduleName, '<?php'.PHP_EOL.$controller->generate());

        return 0;
    }
}