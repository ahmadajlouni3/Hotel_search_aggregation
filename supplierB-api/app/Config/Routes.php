<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// supplier b route
$routes->get('mock/supplierB/search', 'MockSupplierBController::supplierB');