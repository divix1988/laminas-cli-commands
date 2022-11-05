<?php

return [
    'lmc_rbac' => [
        'guards' => [
            \LmcRbacMvc\Guard\RouteGuard::class => [
                'admin*' => ['admin', 'super_admin']
            ]
        ],

        'role_provider' => [
            'LmcRbacMvc\Role\InMemoryRoleProvider' => [
                'super_admin' => [
                    'children' => ['admin'],
                    'permissions' => ['deleteAll']
                ],
                'admin' => [
                    'children' => ['user'],
                    'permissions' => ['delete']
                ],
                'user' => [
                    'permissions' => ['edit']
                ]
            ]
        ],

        'redirect_strategy' => [
            'redirect_when_connected' => true,
            'redirect_to_route_connected' => 'login', //destination for logged in users without access
            'redirect_to_route_disconnected' => 'login', //destination for NOT logged in users
            'append_previous_uri' => true,
            'previous_uri_query_key' => 'redirectTo'
        ],
        /*'unauthorized_strategy' => [
            'template' => 'error/custom-403'
        ],*/
    ]
];
