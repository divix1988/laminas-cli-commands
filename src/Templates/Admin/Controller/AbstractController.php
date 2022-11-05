<?php
namespace %module_name%\Controller;

use Laminas\Mvc\MvcEvent;
use Laminas\Session;

class AbstractController extends \Laminas\Mvc\Controller\AbstractActionController
{
   protected $sessionUser;
   protected $baseUrl;
   
   public function __construct() {
   }
   
   public function onDispatch(MvcEvent $e) {
        $this->baseUrl = $this->getRequest()->getBasePath();
        $this->sessionUser = new Session\Container('user');
        $action = $e->getRouteMatch()->getParam('action', 'index');
        $e->getTarget()->layout()->action = $action;
        
        $e->getViewModel()->setVariable('user', $this->sessionUser->details);
        
        /*if ($this->sessionUser->details && $this->sessionUser->details->getRole() == 'admin') {
            //assign logged-in user object into layout if it's admin user
            $e->getViewModel()->setVariable('user', $this->sessionUser->details);
        } else {
            //redirect user unauthorized user to login page
            $url = $e->getRouter()->assemble(['action' => 'index'], ['name' => 'login']);
            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();
            exit();
        }*/


        return parent::onDispatch($e);
    }

}