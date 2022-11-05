<?php

namespace Divix\Laminas\Cli\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Usage:
 * 
 * "vendor/bin/laminas-cli.bat" mvc:admin --module=<moduleName>
 */
class AdminPanelCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:admin';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new Admin Panel feature.')
            ->setHelp('This command allows you to create a MVC Admin Panel');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating Admin Panel');
        
        $moduleName = $this->getModuleName($input, $output, 'rowset');
        
        $section1->writeln('Start creating new Module.');
        $this->createStaticModule('Admin', $section2);
        $section1->writeln('Start creating config gobal file.');
        $this->createStaticConfig('Admin', 'lmc_rbac.global.php', $section2, true);
        
        $section1->writeln('Start modyfing modules file.');
        $this->modifyModulesConfigFile('Admin', $section2, "'Application'");
        $this->modifyModulesConfigFile('LmcRbacMvc', $section2, ']');
        
        $section1->writeln('Start modyfing Rowset/User.php.');
        $this->alterUserRowset($moduleName, $section2);
        
        $section1->writeln('Start modyfing composer.json file.');
        $this->modifyComposerFile('"Admin\\\": "module/Admin/src/",', $section2, 'Admin', '"Application\\\": "module/Application/src/",');
        
        $section1->writeln('!!! PLEASE RUN composer update TO UPDATE AUTOLOADER OF THE NEW ADMIN MODULE !!!');

        $section1->writeln('Start injecting config lines.');
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
        ],'
            ],
        ], $section2, $moduleName, 'main');


        $section2->writeln('Done creating Admin Panel.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
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
            return;
        }
        
        $this->storeFormContents('UserRegisterForm.php', $moduleName, $abstractContents);
    }
    
    protected function alterUserRowset($moduleName, $section2)
    {   
        if ($this->isJsonMode()) {
            $code = json_encode(['Model\Rowset\User.php' => 'implements \LmcRbacMvc\Identity\IdentityInterface
    ...
    public function getRoles() {
        return [$this->getRole()];
    }']);
            $section2->writeln($code);
            return;
        }
        
        $filePath = self::MODULE_SRC.$moduleName.self::MODULE_MODEL_SRC.'Rowset/User.php';
        require_once($filePath);
        $generator = new \Laminas\Code\Generator\ClassGenerator();
        $class = new \Laminas\Code\Reflection\ClassReflection('\\'.$moduleName.'\Model\Rowset\User');
        $rowset = $generator->fromReflection($class);
        
        $rowset->addMethod(
            'getRoles',
            [],
            \Laminas\Code\Generator\MethodGenerator::FLAG_PUBLIC,
'return [$this->getRole()];'
        );
        $rowset->setImplementedInterfaces(['\Laminas\InputFilter\InputFilterAwareInterface', '\LmcRbacMvc\Identity\IdentityInterface']);
        
        file_put_contents($filePath, "<?php\n".$rowset->generate());
    }
}