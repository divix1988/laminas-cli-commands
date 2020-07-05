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
 * "vendor/bin/laminas-cli.bat" mvc:mariadb-database-connect <db_host> <db_name>
 */
class MariaDbCommand extends AbstractCommand
{
    protected static $defaultName = 'mvc:mariadb-database-connect';

    protected function configure()
    {
        $this
            ->setDescription('Creates a datbase connection to MariaDB\MySQL.')
            ->setHelp('This command allows you to create a datbase connection to MariaDB\MySQL')
            ->addArgument('db_host', InputArgument::REQUIRED, 'The name of the datbase host.')
            ->addArgument('db_name', InputArgument::REQUIRED, 'The name of the datbase.');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $section1 = $output->section();
        $section2 = $output->section();
        $section1->writeln('Start creating a datbase connection');
        
        $host = ucfirst(rtrim($input->getArgument('db_host'), 's'));
        $name = ucfirst(rtrim($input->getArgument('db_name'), 's'));
        
        $code = (json_encode([
                'global.php' => 
'...

\'db\' => array(
    \'driver\' => \'Pdo\',
    \'dsn\' => \'mysql:dbname='.$name.';host='.$host.'\',
    \'driver_options\' => [
        1002 => \'SET NAMES \\\'UTF8\\\'\',
    ],
),

...

\'service_manager\' => [
    \'factories\' => [
        ...
        
        \'Laminas\\Db\\Adapter\\Adapter\' => \'Laminas\\Db\\Adapter\\AdapterServiceFactory\',
        \'Laminas\Db\TableGateway\TableGateway\' => \'Laminas\Db\TableGateway\TableGatewayServiceFactory\',
    ],
],

...',
            'local.php' => 
'...

\'db\' => array(
    \'username\' => \'\',
    \'password\' => \'\',
),

...'
        ]));
        
        $section2->writeln($code);
        
        $section2->writeln('Done creating a datbase connection.');
        
        parent::postExecute($input, $output, $section1, $section2);

        return 0;
    }
}