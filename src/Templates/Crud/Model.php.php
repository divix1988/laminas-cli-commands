<?php

namespace %packageName%\Model;

class %nameUppercase%Table extends AbstractTable
{
    protected $resultsPerPage = 10;
    
    public function getById($id)
    {
        $id = (int) $id;
        $row = $this->getBy(['id' => $id]);
        
        if (!$row) {
            throw new \Exception('%name% not found with id: '.$id);
        }
        return $row;
    }
    
    public function getBy(array $params = array())
    {
        $select = $this->tableGateway->getSql()->select();

        if (!isset($params['page'])) {
            $params['page'] = 0;
        }
        if (isset($params['id'])) {
            $select->where(['id' => $params['id']]);
            $params['limit'] = 1;
        }
        if (isset($params['title'])) {
            $select->where(['title' => $params['title']]);
        }
        if (isset($params['thumb'])) {
            $select->where(['thumb' => $params['thumb']]);
        }
        if (isset($params['limit'])) {
           $select->limit($params['limit']);
        }
        if (isset($params['limit'])) {
            $select->limit($params['limit']);
        }

        $result = (isset($params['limit']) && $params['limit'] == 1)
            ? $this->fetchRow($select)
            : $this->fetchAll($select, ['limit' => $this->resultsPerPage, 'page' => $params['page']]);
        
        return $result;
    }
    
    public function patch($id, $data)
    {
        if (empty($data)) {
            throw new \Exception('missing data to update');
	}
        $passedData = [];
        
        if (!empty($data['title'])) {
            $passedData['title'] = $data['title'];
        }
        if (!empty($data['thumb'])) {
            $passedData['thumb'] = $data['thumb'];
        }
        $this->tableGateway->update($passedData, ['id' => $id]);
    }
    
    public function save(Rowset\Comics $comicsModel)
    {
        return parent::saveRow($comicsModel);
    }
    
    public function delete($id)
    {
        if (empty($id)) {
            throw new \Exception('missing comics id to delete');
	}
        parent::deleteRow($id);
    }
}
