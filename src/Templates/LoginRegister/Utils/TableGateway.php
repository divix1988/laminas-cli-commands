<?php

namespace %module_name%\Utils;

use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Update;
use Laminas\Db\Sql\Delete;

class TableGateway extends \Laminas\Db\TableGateway\TableGateway
{
    protected $platform;

    public function __construct(
        $table,
        \Laminas\Db\Adapter\AdapterInterface $adapter,
        $features = null,
        \Laminas\Db\ResultSet\ResultSetInterface $resultSetPrototype = null,
        \Laminas\Db\Sql\Sql $sql = null
    ) {
        parent::__construct($table, $adapter, $features, $resultSetPrototype, $sql);
        $this->platform = new \Laminas\Db\Adapter\Platform\Mysql($this->adapter->driver);
    }
}

