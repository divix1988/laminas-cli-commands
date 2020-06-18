<?php

namespace %module_name%\Controller;

use %module_name%\Form;
use %module_name%\Model;
use %module_name%\Hydrator;
use Laminas\Session;

class RegisterController extends AbstractController {
    protected $usersModel;
    protected $securityAuth;
    protected $securityHelper;

    public function __construct($usersModel, $securityAuth, $securityHelper)
    {
        $this->usersModel = $usersModel;
        $this->securityAuth = $securityAuth;
        $this->securityHelper = $securityHelper;
    }

    public function indexAction()
    {
        $form = new Form\UserRegisterForm(
            'user_register',
            [
                'dbAdapter' => $this->usersModel->getTableGateway()->getAdapter(),
                'baseUrl' => $this->baseUrl
            ]
        );
        $viewParams = [
            'userForm' => $form
        ];
        
        if ($this->getRequest()->isPost()) { 
            $form->setData($this->getRequest()->getPost());
            
            if ($form->isValid()) {
                $rowset = new Model\Rowset\User();
                $hydrator = new Hydrator\UserFormHydrator($this->securityHelper);
                $formData = $form->getData();
                $rowset->exchangeArray($hydrator->hydrate($form));

                //store to database
                $userId = $this->usersModel->save($rowset);
                $rowset->setId($userId);
                //user logging
                $this->securityAuth->authenticate(
                    $rowset->getEmail(),
                    $formData[$form::FIELDSET_LOGIN][Form\UserLoginFieldset::ELEMENT_PASSWORD]
                );
                $identity = $this->securityAuth->getIdentityArray();
                
                if ($identity) {
                    //session creation
                    $sessionUser = new Session\Container('user');
                    $sessionUser->details = $rowset;
                    
                    return $this->redirect()->toRoute('login', ['action' => 'progressuser']);
                } else {
                    throw new \Exception('Something went wrong.. Check if the user has been added to the database correctly');
                }
            } else {
                $viewParams['messages'] = $form->getMessages();
            }
        }
        return $viewParams;
    }
}

