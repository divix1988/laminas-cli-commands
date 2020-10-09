<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Usage:
 * 
 * "vendor/bin/laminas-cli.bat" mvc:login_registration --properties=<property1> --properties=<property2> --module=<moduleName>
 */
class LoginRegistrationCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:login_registration';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new Users Login & Registration feature.')
            ->setHelp('This command allows you to create a MVC Users Login & Registration')
            ->addOption('properties', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'User property names list');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating Users Login & Registration');
        
        $moduleName = $this->getModuleName($input, $output, 'rowset');
        
        $inputProperties = $this->getPropertiesArray($input);
        
        $defualProperties = [
            'password',
            'passwordSalt',
            'role',
            'username'
        ];
        $properties = array_merge($inputProperties, $defualProperties);
        
        foreach ($defualProperties as $property) {
            if (($key = array_search($property, $properties)) !== false) {
                unset($properties[$key]);
            }
        }
        
        $section1->writeln('Start creating new Model.');
        $this->generateModel($moduleName, 'Users', $output, $properties);
        $section1->writeln('End creating new Model.');
        
        $section1->writeln('Start creating new Rowset.');
        $this->generateRowset($moduleName, 'User', $output, $properties);
        $section1->writeln('End creating new Rowset.');

        $this->createStaticController($moduleName, 'AdminPanel/Controller', 'AbstractController.php', $section2);
        $this->createStaticUtils($moduleName, 'LoginRegister/Utils', 'TableGateway.php', $section2);
        $this->createStaticUtils($moduleName, 'LoginRegister/Utils', 'Adapter.php', $section2);
        $this->createStaticUtils($moduleName, 'LoginRegister/Utils', 'Authentication.php', $section2);
        $this->createStaticUtils($moduleName, 'LoginRegister/Utils', 'Helper.php', $section2);
        $this->createRegisterController($moduleName, $section2);
        $this->createRegisterView($moduleName, $properties, $section2);
        $this->createHydrator($moduleName, $section2);
        $this->createStaticForm($moduleName, 'UserLoginFieldset', $section2);
        $this->createStaticForm($moduleName, 'UserLoginForm', $section2);
        $this->createStaticForm($moduleName, 'UsernameFieldset', $section2);
        $this->createUserRegisterForm($moduleName, $properties, $section2);
        $this->createStaticCss($moduleName, 'LoginRegister/public/css', 'login-register.css', $section2);
        
        
        $this->createLoginController($moduleName, $section2);
        $this->createLoginView($moduleName, $section2);
        $this->createUserController($moduleName, $section2);
        $this->createUserView($moduleName, $section2);
        
        $this->injectConfigCodes([
            'autoload/global.php' => [
                'session' => 
        '\'config\' => [
            \'class\' => \Laminas\Session\Config\SessionConfig::class,
            \'options\' => [
                \'name\' => \'session_name\',
            ],
        ],
        \'storage\' => \Laminas\Session\Storage\SessionArrayStorage::class,
        \'validators\' => [
            \Laminas\Session\Validator\RemoteAddr::class,
            \Laminas\Session\Validator\HttpUserAgent::class,
        ]'
            ],
        ], $section2, $moduleName, 'main');
        
        $this->injectConfigCodes([
            'module.config.php' => [
                'router/routes' =>
'
            \'register\' => [
                \'type\' => Literal::class,
                \'options\' => [
                    \'route\' => \'/register\',
                    \'defaults\' => [
                        \'controller\' => Controller\RegisterController::class,
                        \'action\' => \'index\',
                    ],
                ],
            ],
            \'login\' => [
                \'type\' => Segment::class,
                \'options\' => [
                    \'route\' => \'/login[/:action]\',
                    \'defaults\' => [
                        \'controller\' => Controller\LoginController::class,
                        \'action\' => \'index\',
                    ],
                ],
            ],
',

                'controllers/factories' =>
'
            Controller\RegisterController::class => function($sm) {
                return new Controller\RegisterController(
                    $sm->get(Model\UsersTable::class),
                    $sm->get(Utils\Authentication::class),
                    $sm->get(Utils\Helper::class)
                );
            },
            Controller\LoginController::class => function($sm) {
                return new Controller\LoginController(
                    $sm->get(Utils\Authentication::class)
                );
            },
',
             'service_manager/factories2' =>
'
            \'UsersTableGateway\' => function ($sm) {
                $dbAdapter = $sm->get(\'Laminas\Db\Adapter\Adapter\');
                $resultSetPrototype = new \Laminas\Db\ResultSet\ResultSet();
                //get base url from config
                $config = $sm->get(\'Config\');
                $baseUrl = $config[\'view_manager\'][\'base_url\'];
    
                //pass base url via cnstructor to the User class
                $resultSetPrototype->setArrayObjectPrototype(new Model\Rowset\User($baseUrl));
                return new Utils\TableGateway(\'users\', $dbAdapter, null, $resultSetPrototype);
            },
            \'Application\Model\UsersTable\' => function($sm) {
                $tableGateway = $sm->get(\'UsersTableGateway\');
                $table = new Model\UsersTable($tableGateway);
    
                return $table;
            },
            Utils\Authentication::class => function($sm) {
                $auth = new Utils\Authentication(
                    $sm->get(\Laminas\Db\Adapter\Adapter::class),
                    $sm->get(Utils\Adapter::class)    
                );
                return $auth;
            },
            Utils\Helper::class => InvokableFactory::class,
            
            SessionManager::class => function ($container) {
                $config = $container->get(\'config\');
                $session = $config[\'session\'];
                $sessionConfig = new $session[\'config\'][\'class\']();
                $sessionConfig->setOptions($session[\'config\'][\'options\']);
                $sessionManager = new Session\SessionManager(
                    $sessionConfig,
                    new $session[\'storage\'](),
                    null
                );
                \Laminas\Session\Container::setDefaultManager($sessionManager);
                
                return $sessionManager;
            },
'
            ]
        ], $section2, $moduleName, 'module');
        
        if ($this->isJsonMode()) {
            $code = (json_encode([
                'Module.php' =>
'use Laminas\Session;
    
\Utils\Security\Authentication::class => function($sm) {
    $auth = new \Utils\Security\Authentication(
        $sm->get(\Laminas\Db\Adapter\Adapter::class),
        $sm->get(\Utils\Security\Adapter::class)    
    );
    return $auth;
},
\Utils\Security\Helper::class => InvokableFactory::class,

...

public function onBootstrap($e)
{
    $this->bootstrapSession($e);
}

public function bootstrapSession($e)
{
    $serviceManager = $e->getApplication()->getServiceManager();
    $session = $serviceManager->get(SessionManager::class);
    $session->start();
    $container = new Session\Container(\'initialized\');

    //let’s check if our session is not already created (for the guest or user)
    if (isset($container->init)) {
        return;
    }

    //new session creation
    $request = $serviceManager->get(\'Request\');
    $session->regenerateId(true);
    $container->init = 1;
    $container->remoteAddr = $request->getServer()->get(\'REMOTE_ADDR\');
    $container->httpUserAgent = $request->getServer()->get(\'HTTP_USER_AGENT\');
    $config = $serviceManager->get(\'Config\');
    $sessionConfig = $config[\'session\'];
    $chain = $session->getValidatorChain();

    foreach ($sessionConfig[\'validators\'] as $validator) {
        switch ($validator) {
            case Validator\HttpUserAgent::class:
                $validator = new $validator($container->httpUserAgent);
            break;
            case Validator\RemoteAddr::class:
                $validator = new $validator($container->remoteAddr);
            break;
            default:
                $validator = new $validator();
        }
        $chain->attach(\'session.validate\', array($validator, \'isValid\'));
    }
}'
            ]));
            $section2->writeln($code);
        }

        //$section2->writeln($rowset->generate());
        //$this->storeRowsetContents($name.'.php', $moduleName, '<?php'.PHP_EOL.$rowset->generate());
        $section2->writeln('Done creating Users Login & Registration.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
    
    protected function createRegisterController($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/RegisterController.php');
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode(['RegisterController.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeControllerContents('RegisterController.php', $moduleName, $abstractContents);
    }
    
    protected function createLoginController($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/LoginController.php');
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode(['LoginController.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeControllerContents('LoginController.php', $moduleName, $abstractContents);
    }
    
    protected function createUserController($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/UserController.php');
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $abstractContents = str_replace("<?php", '', $abstractContents);
            $code = (json_encode(['UserController.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeControllerContents('UserController.php', $moduleName, $abstractContents);
    }
    
    protected function createRegisterView($moduleName, $properties, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/View/register.phtml');
        $propertiesCode = '';
        
        foreach ($properties as $property) {
            $propertiesCode .= 
'                            echo $this->formRow($userForm->get(\''.$property.'\'));'.PHP_EOL;
        }
        $abstractContents = str_replace("%properties%", $propertiesCode, $abstractContents);
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['register/index.phtml' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeViewContents('index.phtml', $moduleName, 'register', $abstractContents);
    }
    
    protected function createHydrator($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/Hydrator/UserFormHydrator.php');
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['UserFormHydrator.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeHydratorContents('index.phtml', $moduleName, $abstractContents);
    }
    
    protected function createStaticForm($moduleName, $filename, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/Form/'.$filename.'.php');
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        if ($this->isJsonMode()) {
            $code = (json_encode([$filename.'.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeFormContents($filename.'.php', $moduleName, $abstractContents);
    }
    
    protected function createUserRegisterForm($moduleName, $properties, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/Form/UserRegisterForm.php');
        $abstractContents = str_replace("%module_name%", $moduleName, $abstractContents);
        
        $propertiesCode = '';
        
        foreach ($properties as $property) {
            $propertiesCode .= 
'$this->add([
    \'name\' => \''.$property.'\',
    \'type\' => \'text\',
    \'options\' => [
        \'label\' => \''.ucfirst($property).'\'
    ],
    \'attributes\' => [
        \'required\' => true
    ]
]);'.PHP_EOL;
        }
        $abstractContents = str_replace("%properties%", $propertiesCode, $abstractContents);
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['UserRegisterForm.php' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeFormContents('UserRegisterForm.php', $moduleName, $abstractContents);
    }
    
    protected function createUserView($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/View/user.phtml');
        
        //@TODO amend user properties 
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['user/index.phtml' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeViewContents('index.phtml', $moduleName, 'user', $abstractContents);
    }
    
    protected function createLoginView($moduleName, $section2)
    {
        $abstractContents = file_get_contents(__DIR__.'/Templates/LoginRegister/View/login.phtml');
        
        if ($this->isJsonMode()) {
            $code = (json_encode(['login/index.phtml' => $abstractContents]));
            $section2->writeln($code);
        }
        
        $this->storeViewContents('index.phtml', $moduleName, 'login', $abstractContents);
    }
}