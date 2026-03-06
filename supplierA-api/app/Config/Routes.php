<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');


// supplier a route
$routes->get('mock/supplierA/search', 'MockSupplierAController::supplierA');