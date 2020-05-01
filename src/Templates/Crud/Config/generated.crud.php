<?php

namespace %module_name%;

use Laminas\Router\Http\Segment;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use %module_name%\Model\Rowset;
use %module_name%\Model;
use %module_name%\Controller;

return [
    'router' => [
        'routes' => [
            '%name_plural_lower%' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/%name_plural_lower_all%[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\%name_plural_upper%Controller::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'paginator' => [
                        'type' => 'segment',
                        'options' => [
                            'route' => '/[page/:page]',
                            'defaults' => [
                                'page' => 1
                            ]
                        ]
                    ],
                ]
            ]
        ]
    ],
    
    'controllers' => [
        'factories' => [
            Controller\%name_plural_upper%Controller::class => function($sm) {
                $postService = $sm->get(Model\%name_plural_upper%Table::class);

                return new Controller\%name_plural_upper%Controller($postService);
            },
        ]
    ],
                    
    'service_manager' => [
        'factories' => [
            '%name_plural_upper%TableGateway' => function ($sm) {
                $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                $config = $sm->get('Config');
                $baseUrl = $config['view_manager']['base_url'];
                $resultSetPrototype = new ResultSet();
                $identity = new Rowset\%name_singular_upper%($baseUrl);
                $resultSetPrototype->setArrayObjectPrototype($identity);
                return new TableGateway('%name_plural_lower%', $dbAdapter, null, $resultSetPrototype);
            },
            Model\%name_plural_upper%Table::class => function($sm) {
                $tableGateway = $sm->get('%name_plural_upper%TableGateway');
                $table = new Model\%name_plural_upper%Table($tableGateway);
                return $table;
            },
        ]
    ],
                    
    'view_manager' => [
        'template_map' => [
            '%module_name_plural_dashed%/%name_plural_dashed%/index' => __DIR__ . '/../view/%name_plural_upper%/index.phtml',
            '%module_name_plural_dashed%/%name_plural_dashed%/edit' => __DIR__ . '/../view/%name_plural_upper%/edit.phtml',
            '%module_name_plural_dashed%/%name_plural_dashed%/add' => __DIR__ . '/../view/%name_plural_upper%/add.phtml',
            '%module_name_plural_dashed%/%name_plural_dashed%/pagination' => __DIR__ . '/../view/%name_plural_upper%/pagination.phtml',
        ],
    ]
];
