<?php

namespace Router;

class Routes {
    public static string $defaultNamespace = 'controller\\';
    public static string $defaultHome = '/';
	public static string $defaultMethod = 'index';

    public static array $routes = [
        '/' => 'GET|POST|Home',
        'users' => ['GET|POST|Users', 'index,add,edit,delete,grid,clear,show'],
        'categories' => ['GET|POST|Categories', 'index,add,edit,delete,grid,clear,show'],
        'customers' => ['GET|POST|Customers', 'index,add,edit,delete,grid,clear,show'],
        'employees' => ['GET|POST|Employees', 'index,add,edit,delete,grid,clear,show'],
        'territories' => ['GET|POST|Territories', 'index,add,edit,delete,grid,clear,show'],
        'suppliers' => ['GET|POST|Suppliers', 'index,add,edit,delete,grid,clear,show'],
        'shippers' => ['GET|POST|Shippers', 'index,add,edit,delete,grid,clear,show'],
        'regions' => ['GET|POST|Regions', 'index,add,edit,delete,grid,clear,show'],
        'products' => ['GET|POST|Products', 'index,add,edit,delete,grid,clear,show'],
        'orders' => ['GET|POST|Orders', 'index,add,edit,delete,grid,clear,show'],
        'OrderDetails' => ['GET|POST|OrderDetails', 'index,add,edit,delete,grid,selectorder'],
        'ordersextended' => ['GET|POST|OrdersExtended', 'index,grid,clear,show'],
        'ordersperyear' => ['GET|POST|OrdersPerYear', 'index,grid'],
        'users' => ['GET|POST|Users', 'index,add,edit,delete,grid,clear,show'],
        'roles' => ['GET|POST|Roles', 'index,add,edit,delete,grid,clear,show'],
        'resources' => ['GET|POST|Resources', 'index,add,edit,delete,grid,clear,show'],
        'roles_resources' => ['GET|POST|RolesResources', 'index,add,edit,delete,grid,clear,show'],
        'upgrade' => ['GET|POST|Upgrade', 'index'],
        'clientRequests' => ['GET|clientRequests', 'index,CustomersSearch,ProductsSearch']
//       'init' => ['GET|Init', 'index']
    ];

    public static array $auth_exceptions = [
    ];
}  