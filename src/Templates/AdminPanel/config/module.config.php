<?php

namespace %module_name%;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

$provider = new ConfigProvider();

return [
    'service_manager' => $provider->getDependencyConfig(),
    'view_manager' => $provider->getViewManagerConfig(),
    'admin' => $provider->getModuleConfig(),
    'controllers' => [
        'factories' => [
            Controller\AdminController::class => function($sm) {
                return new Controller\AdminController());
            }
        ],
    ],
    'navigation' => array(
        'admin' => array(
            'home' => array(
                'label' => 'Home Page',
                'route' => 'admin',
            ),
            'logout' => array(
                'label' => 'Logout',
                'route' => 'logout',
            ),
        ),
    ),
    'router' => [
        'routes' => [
            'admin' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin',
                    'defaults' => [
                        'controller' => Controller\AdminController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                ]
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login/logout',
                    'defaults' => [
                        'controller' => \Application\Controller\LoginController::class,
                        'action' => 'logout',
                    ],
                ],
                'may_terminate' => true,
            ],
        ],
    ],
];
