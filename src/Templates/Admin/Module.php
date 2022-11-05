<?php

namespace %module_name%;

use Laminas\EventManager\EventInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\V2RouteMatch;
use Laminas\Router\RouteMatch as V3RouteMatch;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(EventInterface $e)
    {
        $app = $e->getParam('application');
        $em = $app->getEventManager();
        $em->attach(MvcEvent::EVENT_DISPATCH, [$this, 'selectLayoutBasedOnRoute']);
        
        $sm = $app->getServiceManager();
	$listener = $sm->get(\ZfcRbac\View\Strategy\RedirectStrategy::class);
	$listener->attach($em);
    }

    public function selectLayoutBasedOnRoute(MvcEvent $e)
    {
        $app = $e->getParam('application');
        $sm = $app->getServiceManager();
        $config = $sm->get('config');

        if ($config['admin']['use_admin_layout'] === false) {
            return;
        }
        $match = $e->getRouteMatch();
        $controller = $e->getTarget();

        if (!($match instanceof V2RouteMatch || $match instanceof V3RouteMatch)
            || 0 !== strpos($match->getMatchedRouteName(), 'admin')
            || ($controller->getEvent()->getResult() && $controller->getEvent()->getResult()->terminate())
        ) {
            return;
        }
        $layout = $config['admin']['admin_layout_template'];
        $controller->layout($layout);
    }
}
