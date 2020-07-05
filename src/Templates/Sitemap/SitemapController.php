<?php

namespace %module_name%\Controller;

use Laminas\View\Model\ViewModel;

class SitemapController extends extends \Laminas\Mvc\Controller\AbstractActionController
{
    private $navigation;

    public function __construct($navigation)
    {
        $this->navigation = $navigation;
    }

    public function indexAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'text/xml');
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        
        return $viewModel;
    }
}

