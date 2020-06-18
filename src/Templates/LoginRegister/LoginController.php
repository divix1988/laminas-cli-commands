<?php

namespace %module_name%\Controller;

use %module_name%\Model;
use %module_name%\Form;
use Laminas\Session;

class LoginController extends AbstractController
{
    protected $securityAuth;

    public function __construct($securityAuth)
    {
        $this->securityAuth = $securityAuth;
    }
    public function indexAction()
    {
        $form = new Form\UserLoginForm();
        //print_r($this);
        if (!$this->getRequest()->isPost()) {
            return [
                'form' => $form
            ];
        }
        $form->setData($this->getRequest()->getPost());

        if (!$form->isValid()) {
            return [
                'form' => $form,
                'messages' => $form->getMessages()
            ];
        }
        $auth = $this->securityAuth->auth(
            $form->get($form::FIELDSET_LOGIN)->get('email')->getValue(),
            $form->get($form::FIELDSET_LOGIN)->get('password')->getValue()
        );
        $identity = $this->securityAuth->getIdentityArray();
        
        if ($identity) {
            $rowset = new Model\Rowset\User();
            $rowset->exchangeArray($identity);
            $this->securityAuth->getStorage()->write($rowset);
            
            $sessionUser = new Session\Container('user');
            $sessionUser->details = $rowset;
            $redirectParam = '';
	
            if (!empty($this->params()->fromQuery('redirectTo'))) {
                $redirectParam = '?redirectTo='.$this->params()->fromQuery('redirectTo');
            }
            return $this->redirect()->toUrl('login/progressuser'.$redirectParam);
        } else {
            $message = '<strong>Error</strong> Given email address or password is incorrect.';
            return [
                'form' => $form,
                'messages' => $message
            ];
        }
    }

    public function progressUserAction()
    {
        $sessionUser = new Session\Container('user');

        if (!empty($this->params()->fromQuery('redirectTo'))) {
            return $this->redirect()->toUrl($this->params()->fromQuery('redirectTo'), 302);
        }

        if ($sessionUser->details->getRole() === 'admin' || $sessionUser->details->getRole() === 'super_admin') {
            $this->redirect()->toRoute('admin', ['controller' => 'IndexController', 'action' => 'index']);
        } else if($sessionUser->details->getRole() === 'user') {
            $this->redirect()->toRoute('user');
        }
    }

    public function logoutAction()
    {
        $session = new Session\Container('user');
	$session->getManager()->destroy();
	$this->redirect()->toRoute('home');
    }
}
