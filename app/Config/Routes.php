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
$routes->setAutoRoute(false); 

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
//$routes->get('/', 'Home::index');
//$routes->get('pages', 'Pages::index');
//$routes->get('pages', [Pages::class, 'index']);
//$routes->get('(:segment)', [Pages::class, 'view']);

$routes->get('/', 'Main::index');
$routes->get('logout', 'Main::logout');

$routes->post('main', 'Main::login_action');

$routes->get('database', 'Database::index');
$routes->get('/database/contacts', 'Database::contacts');
$routes->post('/database/contacts', 'Database::contacts');
$routes->get('/database/companies', 'Database::companies');
$routes->post('/database/companies', 'Database::companies');

$routes->get('directory', 'ExhibitorDirectory::form_show');
$routes->post('directory', 'ExhibitorDirectory::data_submitted');
$routes->post('upload/do_upload', 'Upload::do_upload');

$routes->get('/expo', 'Expo::index');          
$routes->post('/expo', 'Expo::index');

$routes->get('/expo/list_expo_entries_mesa', 'Expo::list_expo_entries_mesa');
$routes->get('/badge', 'Badgemesa::index');          
$routes->post('/badge', 'Badgemesa::index');
$routes->get('/badgemesa/BadgesMesaBlankProfessional', 'Badgemesa::BadgesMesaBlankProfessional');          
$routes->post('/badgemesa/BadgesMesaBlankProfessional', 'Badgemesa::BadgesMesaBlankProfessional');
$routes->get('/badgemesa/Blankbadge', 'Badgemesa::Blankbadge');          
$routes->post('/badgemesa/Blankbadge', 'Badgemesa::Blankbadge');

$routes->get('/badgemesa/BadgesMesaProfessional', 'Badgemesa::BadgesMesaProfessional');          
$routes->post('/badgemesa/BadgesMesaProfessional', 'Badgemesa::BadgesMesaProfessional');

$routes->get('badgemesa/BadgesMesaBlankExhibitor', 'Badgemesa::BadgesMesaBlankExhibitor');          
$routes->post('badgemesa/BadgesMesaBlankExhibitor', 'Badgemesa::BadgesMesaBlankExhibitor');

$routes->get('/badgemesa/BadgesMesaBlankEXPO', 'Badgemesa::BadgesMesaBlankEXPO');          
$routes->post('/badgemesa/BadgesMesaBlankEXPO', 'Badgemesa::BadgesMesaBlankEXPO');

$routes->get('/badgemesa/BadgesMesaExhibitor', 'Badgemesa::BadgesMesaExhibitor');          
$routes->post('/badgemesa/BadgesMesaExhibitor', 'Badgemesa::BadgesMesaExhibitor');

$routes->get('/badgemesa/BadgesMesaEXPOONLY', 'Badgemesa::BadgesMesaEXPOONLY');          
$routes->post('/badgemesa/BadgesMesaEXPOONLY', 'Badgemesa::BadgesMesaEXPOONLY');

$routes->get('/badgemesa/TestConXsingle/(:num)', 'Badgemesa::TestConXsingle');          
$routes->post('/badgemesa/TestConXsingle/(:num)', 'Badgemesa::TestConXsingle');


$routes->get('/badgemesa/testconxguests', 'Badgemesa::testconxguests');          
$routes->post('/badgemesa/testconxguests', 'Badgemesa::testconxguests');


$routes->get('/testtcpdf', 'Badges\Tcpdfexample');        
$routes->post('/testtcpdf', 'Badges\Tcpdfexample');

$routes->get('/mypdf', 'mypdf::index');          
$routes->post('/mypdf', 'mypdf::index');

$routes->get('/mypdf2', 'Mypdf2');          
$routes->post('/mypdf2', 'Mypdf2');

$routes->get('/certificates', 'Certificate::index');          
$routes->post('/certificates', 'Certificate::index');

$routes->get('/Certificate/CertificatesGeneral', 'Certificate::CertificatesGeneral');          
$routes->post('/Certificate/CertificatesGeneral', 'Certificate::CertificatesGeneral');

$routes->get('/Certificate/Certificates', 'Certificate::Certificates');          
$routes->post('/Certificate/Certificates', 'Certificate::Certificates');


/* $routes->get('/expo/contact', 'Expo::contact1337');          
$routes->post('/expo/contact', 'Expo::contact1337');
$routes->get('/Expo/duplicate', 'Expo::duplicate');          
$routes->post('/Expo/duplicate', 'Expo::duplicate');

$routes->get('/expo/list_expo_entries_mesa', 'Expo::list_expo_entries_mesa'); 
$routes->get('/expo/list_expo_entries_suzhou', 'Expo::list_expo_entries_suzhou'); 
$routes->get('/expo/list_expo_entries_shenzhen', 'Expo::list_expo_entries_shenzhen'); 
$routes->get('/expo/list_expo_entries_shanghai', 'Expo::list_expo_entries_shanghai');
$routes->get('/expo/list_expo_entries_korea', 'Expo::list_expo_entries_korea');

$routes->get('/expo/list_expo_entries_china', 'Expo::list_expo_entries_china'); */


/*
$routes->get('/upload', 'Upload2::form_show');          
$routes->post('/upload/upload', 'Upload2::upload'); 

$routes->get('/Certificate', 'Certificate::index');          
$routes->post('/Certificate', 'Certificate::index'); 
$routes->get('/Certificate/Certificates', 'Certificate::Certificates');          
$routes->post('/Certificate/Certificates', 'Certificate::Certificates'); 

$routes->get('/Certificate/CertificatesGeneral', 'Certificate::CertificatesGeneral');          
$routes->post('/Certificate/CertificatesGeneral', 'Certificate::CertificatesGeneral'); 


$routes->get('/expo/contact', 'Expo::contact1337');          
$routes->post('/expo/contact', 'Expo::contact1337');
$routes->get('/Expo/duplicate', 'Expo::duplicate');          
$routes->post('/Expo/duplicate', 'Expo::duplicate');

*/

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
