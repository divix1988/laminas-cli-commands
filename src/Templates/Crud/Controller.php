<?php

namespace %packageName%\Controller;

use %packageName%\Model\%name%Table;

class %nameUppercase%Controller extends \Laminas\Mvc\Controller\AbstractActionController
{
    protected $%name%Table = null;
    
    public function __construct(%name%Table $%name%Table)
    {
        $this->%name%Table = $%name%Table;
    }
    public function indexAction()
    {
        return [
            '%name%' => $this->%name%Table->getBy(['page' => $this->params()->fromRoute('page')])
        ];
    }
}