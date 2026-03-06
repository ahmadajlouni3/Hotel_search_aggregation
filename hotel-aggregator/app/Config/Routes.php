<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');


// custom routes
// search route
$routes->get('api/hotels/search', "SearchController::hotels");