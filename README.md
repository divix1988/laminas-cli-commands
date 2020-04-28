# laminas-cli-commands

CLI Commands for Laminas projects

## Installation

### Via Composer

Install the library using [Composer](https://getcomposer.org):

```bash
$ composer require --dev divix1988/laminas-cli-commands
```

## Setup
Add the following config into your `config/local.php` file:
```php
'laminas-cli' => [
    'commands' => [
        'mvc:controller' => \Divix\Laminas\Cli\Command\ControllerCommand::class,
        'mvc:rowset' => \Divix\Laminas\Cli\Command\RowsetCommand::class,
        'mvc:model' => \Divix\Laminas\Cli\Command\ModelCommand::class,
        'mvc:form' => \Divix\Laminas\Cli\Command\FormCommand::class,
        'mvc:view' => \Divix\Laminas\Cli\Command\ViewCommand::class,
        'mvc:crud' => \Divix\Laminas\Cli\Command\CrudCommand::class,
        'mvc:crud_controller' => \Divix\Laminas\Cli\Command\CrudControllerCommand::class,
        'mvc:crud_view' => \Divix\Laminas\Cli\Command\CrudViewCommand::class,
        'mvc:crud_model' => \Divix\Laminas\Cli\Command\CrudModelCommand::class,
    ],
],
```

## Usage
```bash
$ vendor/bin/laminas [command-params] [command-name]
```
or for Windows users:
```bash
$ "vendor/bin/laminas.bat" [command-params] [command-name]
```

## Available commands

### Controller
Generate sample controller with a list of available actions:
```bash
"vendor/bin/laminas-cli.bat" mvc:controller --actions=<action1> --actions=<action2> --module=ModuleName <name>
```
New file in: `[root]/module/[moduleName]/src/Controller/[name].php`

Sample output:
```php
namespace ModuleName\Controller\ControllerNameController;

class ControllerNameController extends \Laminas\Mvc\Controller\AbstractActionController
{

    public function indexAction()
    {
    }

    public function action1Action()
    {
    }

    public function action2Action()
    {
    }


}
```

### Model
Generate sample model with a list of properties:
```bash
"vendor/bin/laminas-cli.bat" mvc:model --properties=<property1> --properties=<property2> --module=ModuleName <name>
```
New file in: `[root]/module/[moduleName]/src/Model/[name].php`

Sample output:
```php
namespace ModuleName\Model;

class ModelNameTable extends AbstractTable
{

    protected $resultsPerPage = 10;

    public function getBy(array $params = [])
    {
        $select = $this->tableGateway->getSql()->select();

        if (isset($params['id'])) {
            $select->where(['id' => $params['id']]);
            $params['limit'] = 1;
        }

        if (isset($params['property1'])) {
            $select->where(['property1' => $params['property1']]);
        }

        if (isset($params['property2'])) {
            $select->where(['property2' => $params['property2']]);
        }



        if (isset($params['limit'])) {
            $select->limit($params['limit']);
        }

        if (!isset($params['page'])) {
            $params['page'] = 0;
        }

        $result = (isset($params['limit']) && $params['limit'] == 1)
            ? $this->fetchRow($select)
            : $this->fetchAll($select, ['limit' => $this->resultsPerPage, 'page' => $params['page']]);

        return $result;
    }

    public function patch(int $id, array $data)
    {
        if (empty($data)) {
            throw new \Exception('missing data to update');
        }
        $passedData = [];

        if (!empty($data['property1'])) {
            $passedData['property1'] = $data['property1'];
        }

        if (!empty($data['property2'])) {
            $passedData['property2'] = $data['property2'];
        }


        $this->tableGateway->update($passedData, ['id' => $id]);
    }

    public function getById(\Rowset\ModelName $rowset)
    {
        return parent::saveRow($rowset);
    }

    public function delete($id)
    {
        if (empty($id)) {
            throw new \Exception('missing comics id to delete');
        }
        parent::deleteRow($id);
    }


}
```

### Form
Generate sample model with a list of properties:
```bash
"vendor/bin/laminas-cli.bat" mvc:form --properties=<property1> --properties=<property2> --module=<ModuleName> <name>
```
New file in: `[root]/module/[moduleName]/src/Form/[name]Form.php`

Sample output:
```php
namespace ModuleName\Form;

use \Laminas\Form\Element;

class NewUserForm extends \Laminas\Form\Form implements \Laminas\InputFilter\InputFilterProviderInterface
{

     const ELEMENT_PROPERTY1 = 'property1';

     const ELEMENT_PROPERTY2 = 'property2';

    public function __construct($name, array $params)
    {
        parent::__construct($name, $params);
        $this->setAttribute('class', 'styledForm');
        
        $this->add([
            'name' => self::ELEMENT_PROPERTY1,
            'type' => 'text',
            'options' => [
                'label' => 'property1'
            ],
            'attributes' => [
                'required' => true
            ]
        ]);
        
        $this->add([
            'name' => self::ELEMENT_PROPERTY2,
            'type' => 'text',
            'options' => [
                'label' => 'property2'
            ],
            'attributes' => [
                'required' => true
            ]
        ]);
        
        
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Submit',
                'class' => 'btn btn-primary'
            ]
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [];
    }


}
```

### Rowset
Generate sample rowset with a list of params:
```bash
"vendor/bin/laminas-cli.bat" mvc:rowset --properties=<property1> --properties=<property2> --module=ModuleName <name>
```
New file in: `[root]/module/[moduleName]/src/Model/Rowset/[name].php`

Sample output:
```php
namespace ModuleName\Model;

class RowsetName
{

    public $property1 = null;

    public $property2 = null;

    public function getProperty1()
    {
        return $this->property1;
    }

    public function setProperty1($value)
    {
        $this->property1 = $value;
        return $this;
    }

    public function getProperty2()
    {
        return $this->property2;
    }

    public function setProperty2($value)
    {
        $this->property2 = $value;
        return $this;
    }


}
```

### View
Generate sample .phtml view file:
```bash
"vendor/bin/laminas-cli.bat" mvc:view --module=ModuleName <controllerName> <name>
```
New file in: `[root]/module/[moduleName]/view/[controllerName]/[name].phtml`

Sample output:
```php
<p>ModuleName - ViewName</p>
```

### CRUD [WIP]
Generate a full working example with Form, Rowset, Model, View and Controller with given name:
```bash
"vendor/bin/laminas-cli.bat" mvc:crud --properties=<property1> --properties=<property2> --module=ModuleName <name>
```
New files in: 
```
[root]/module/[moduleName]/src/Controller/[name]Controller.php
[root]/module/[moduleName]/src/Model/[name]Model.php
[root]/module/[moduleName]/src/Model/AbstractModel.php
[root]/module/[moduleName]/src/Form/[name]Form.php
[root]/module/[moduleName]/src/Model/Rowset/[name].php
[root]/module/[moduleName]/view/[name]/index.phtml
[root]/module/[moduleName]/view/[name]/add.phtml
[root]/module/[moduleName]/view/[name]/delete.phtml
[root]/module/[moduleName]/view/[name]/edit.phtml
```

Configuration in:
```php
config/generate.php
```
