<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
//$routes->get('/', 'Home::index');
$routes->get('/', 'Database::index');
$routes->get('/database/contacts', 'Database::contacts');
$routes->post('/database/contacts', 'Database::contacts');
$routes->get('/database/companies', 'Database::companies');
$routes->post('/database/companies', 'Database::companies');
$routes->get('/test5', 'Databaseold::customers');
$routes->post('/test5', 'Databaseold::customers');
$routes->get('/Asiaguest/test1', 'Asiaguest::company123');
$routes->post('/Asiaguest/test1', 'Asiaguest::company123');
$routes->get('/test', 'Asiaguest::testhello');
$routes->post('/test', 'Asiaguest::testhello');
$routes->get('/test2', 'Asiaguest::customers');
$routes->post('/test2', 'Asiaguest::customers');
$routes->get('/test3', 'shorttest::customers');
$routes->post('/test3', 'shorttest::customers');
$routes->get('/test4', 'Database::customers');
$routes->post('/test4', 'Database::customers');
$routes->get('/Asiaguest2/test2', 'Asiaguest2::customers');
$routes->post('/Asiaguest2/test2', 'Asiaguest2::customers');
$routes->get('/test6', 'Asiaguest::contact585442');
$routes->post('/test6', 'Asiaguest::contact585442');
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
