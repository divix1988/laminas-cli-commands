<?php

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Application\Model\Rowset\AbstractModel;
use Laminas\Paginator\Adapter\DbSelect;
use Laminas\Paginator\Paginator;
use Laminas\Cache\StorageFactory;

class AbstractTable
{
    protected $tableGateway;
    public static $paginatorCache;
    public static $paginatorCacheEnabled = true;
        
    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
        
        if (empty(self::$paginatorCache)) {
            // set a cache in form of text files in folder data/cache and
            // apply serialize convertion for storing data
            // our copy will be deleted after 10 minutes (600 seconds)
            self::$paginatorCache = StorageFactory::factory([
                'adapter' => [
                    'name' => 'filesystem',
                    'options' => [
                        'cache_dir' => 'data/cache',
                        'ttl' => 600
                    ]
                ],
                'plugins' => ['serializer'],
            ]);
            Paginator::setCache(self::$paginatorCache);
        }
    }
    
    public function disableCache()
    {
	self::$paginatorCache = 'disabled';
	self::$paginatorCacheEnabled = false;
    }
    
    protected function fetchAll($select, array $paginateOptions = null)
    {
        if (!empty($paginateOptions)) {
            // create first adapter, which we will pass to he paginator
            $paginatorAdapter = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $this->tableGateway->getResultSetPrototype()
            );
            $paginator = new Paginator($paginatorAdapter);
            $paginator->setCacheEnabled(self::$paginatorCacheEnabled);
            // set number of records per pgae
            $paginator->setItemCountPerPage($paginateOptions['limit']);
            // if we are passing page parameter, then we set offset for the results
            if (isset($paginateOptions['page'])) {
                $paginator->setCurrentPageNumber($paginateOptions['page']);
            }
            return $paginator;
        }
        return $this->tableGateway->select();
    }
    
    protected function fetchRow($passedSelect)
    {
        $row = $this->tableGateway->selectWith($passedSelect);
        return $row->current();
    }
    
    public function saveRow(AbstractModel $userModel, $data = null)
    {
        $id = $userModel->getId();
        //if the parameter $data is not passed in, then update all of the object’s properties
        if (empty($data)) {
            $data = $userModel->getArrayCopy();
        }
        if (empty($id)) {
            $this->tableGateway->insert($data);
            return $this->tableGateway->getLastInsertValue();
        }
        if (!$this->getById($id)) {
            throw new RuntimeException(get_class($userModel) .' with id: '.$id.' not found');
        }
        $this->tableGateway->update($data, ['id' => $id]);
        return $id;
    }
    
    public function deleteRow($id)
    {
        $this->tableGateway->delete(['id' => (int) $id]);
    }
    
    public function getTableGateway()
    {
        return $this->tableGateway;
    }
}
