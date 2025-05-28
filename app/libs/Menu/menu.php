<?php
// MenuText => Route
return [
    'Tables' =>  [
        'Categories' => '/categories',
        'Customers' => '/customers',
        'Employees' => '/employees',
        'Orders' => '/orders',
        'Products' => '/products',
        'Regions' => '/regions',
        'Shippers' => '/shippers',
        'Suppliers' => '/suppliers',
        'Territories' => '/territories'
    ],
    'Queries' =>  [
        'Orders Subtotals' => '/ordersextended',
        'Orders per Year' => '/ordersperyear',
        'etc...' => '/#'
    ],
    'Administration' =>  [
        'Users' => '/users',
        'Permissions' => [
            'Roles' => '/roles',
            'Resources' => '/resources',
            'Role-Resources' => '/roles_resources'
        ],
        'Upgrade' => '/upgrade'
    ]
]; 