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

$routes->get('uploadevent', 'uploadevent::index');          // Add this line.
$routes->post('uploadevent/uploadevent', 'uploadevent::uploadevent'); // Add this line.

$routes->get('logout', 'Main::logout');

$routes->post('main', 'Main::login_action');

$routes->get('database', 'Database::index');
$routes->get('/database/contacts', 'Database::contacts');
$routes->post('/database/contacts', 'Database::contacts');
$routes->get('/database/companies', 'Database::companies');
$routes->post('/database/companies', 'Database::companies');

$routes->get('/mailinglist', 'Mailinglist::index');          
$routes->post('/mailinglist', 'Mailinglist::index');

$routes->get('mailinglist/preview_Guest_to_Main', 'Mailinglist::preview_Guest_to_Main');          
$routes->post('mailinglist/preview_Guest_to_Main', 'Mailinglist::preview_Guest_to_Main');

$routes->get('mailinglist/update_Guest_to_Main', 'Mailinglist::update_Guest_to_Main');          
$routes->post('mailinglist/update_Guest_to_Main', 'Mailinglist::update_Guest_to_Main');



$routes->get('mailinglist/check_add_to_attendance', 'Mailinglist::check_add_to_attendance');          
$routes->post('mailinglist/check_add_to_attendance', 'Mailinglist::check_add_to_attendance');

$routes->get('mailinglist/add_to_attendance', 'Mailinglist::add_to_attendance');          
$routes->post('mailinglist/add_to_attendance', 'Mailinglist::add_to_attendance');




$routes->get('mailinglist/testcontacts', 'Mailinglist::testcontacts');          
$routes->post('mailinglist/testcontacts', 'Mailinglist::testcontacts');



$routes->get('/mailinglist/write_mailchimp_with_Chinese', 'Mailinglist::write_mailchimp_with_Chinese');          
$routes->post('/mailinglist/write_mailchimp_with_Chinese', 'Mailinglist::write_mailchimp_with_Chinese');


$routes->get('/mailinglist/write_mailchimp_no_Chinese', 'Mailinglist::write_mailchimp_no_Chinese');          
$routes->post('/mailinglist/write_mailchimp_no_Chinese', 'Mailinglist::write_mailchimp_no_Chinese');



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

$routes->get('/badgemesa/clearprint', 'Badgemesa::clearprint');          
$routes->post('/badgemesa/clearprint', 'Badgemesa::clearprint');

$routes->get('/badgemesa/BadgestinymlProfessional', 'Badgemesa::BadgestinymlProfessional');          
$routes->post('/badgemesa/BadgestinymlProfessional', 'Badgemesa::BadgestinymlProfessional');



$routes->get('/badgemesa/BadgesEMEAAttendee', 'Badgemesa::BadgesEMEAAttendee');          
$routes->post('/badgemesa/BadgesEMEAAttendee', 'Badgemesa::BadgesEMEAAttendee');

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

$routes->get('/Guest/TestConXsingle/(:num)', 'Guest::TestConXsingle');       
$routes->post('/Guest/TestConXsingle/(:num)', 'Guest::TestConXsingle');

$routes->get('/Guest/multigrid', 'Guest::multigrid');       
$routes->post('/Guest/multigrid', 'Guest::multigrid');

$routes->get('/Guest/testgrid1', 'Guest::testgrid1');       
$routes->post('/Guest/testgrid1', 'Guest::testgrid1');

$routes->get('/Guest/testgrid2', 'Guest::testgrid2');       
$routes->post('/Guest/testgrid2', 'Guest::testgrid2');

$routes->get('/D2000/moveD2000', 'D2000::moveD2000');       
$routes->post('/D2000/moveD2000', 'D2000::moveD2000');

$routes->get('/Dtwomil/moveDtwomil', 'Dtwomil::moveDtwomil');       
$routes->post('/Dtwomil/moveDtwomil', 'Dtwomil::moveDtwomil');

$routes->get('/Dtwomil', 'Dtwomil::index');       
$routes->post('/Dtwomil', 'Dtwomil::index');

$routes->get('/badgemesa/testconxguests', 'Badgemesa::testconxguests');          
$routes->post('/badgemesa/testconxguests', 'Badgemesa::testconxguests');

$routes->get('/badgemesa/Badgenumber', 'Badgemesa::Badgenumber');          
$routes->post('/badgemesa/Badgenumber', 'Badgemesa::Badgenumber');


$routes->get('/badgemesa/Related', 'Badgemesa::Related');          
$routes->post('/badgemesa/Related', 'Badgemesa::Related');


$routes->get('/badgetest', 'Badgemesatest::index');          
$routes->post('/badgetest', 'Badgemesatest::index');

$routes->get('/badgemesatest/test', 'Badgemesatest::test');          
$routes->post('/badgemesatest/test', 'Badgemesatest::test');

$routes->get('/testtcpdf', 'Badges\Tcpdfexample');        
$routes->post('/testtcpdf', 'Badges\Tcpdfexample');

$routes->get('/mypdf', 'mypdf::index');          
$routes->post('/mypdf', 'mypdf::index');

$routes->get('/mypdf2', 'Mypdf2');          
$routes->post('/mypdf2', 'Mypdf2');

$routes->get('/certificates', 'Certificate::index');          
$routes->post('/certificates', 'Certificate::index');



$routes->get('/Certificate/CertificatesGeneralKorea', 'Certificate::CertificatesGeneralKorea');          
$routes->post('/Certificate/CertificatesGeneralKorea', 'Certificate::CertificatesGeneralKorea');

$routes->get('/Certificate/CertificatesGeneralChina', 'Certificate::CertificatesGeneralChina');          
$routes->post('/Certificate/CertificatesGeneralChina', 'Certificate::CertificatesGeneralChina');

$routes->get('/Certificate/CertificatesGeneralMesa', 'Certificate::CertificatesGeneralMesa');          
$routes->post('/Certificate/CertificatesGeneralMesa', 'Certificate::CertificatesGeneralMesa');

$routes->get('/Certificate/Certificates', 'Certificate::Certificates');          
$routes->post('/Certificate/Certificates', 'Certificate::Certificates');

$routes->get('/uploadtest', 'Uploadtest::index');          
$routes->post('/Uploadtest/upload', 'Uploadtest::upload'); 

$routes->get('/print', 'PrintBadge::index');
$routes->post('/PrintBadge/print', 'PrintBadge::print');

$routes->get('/print', 'PrintBadge::index');
$routes->post('/PrintBadge/print', 'PrintBadge::print');
$routes->get('/PrintBadge/printpreview', 'PrintBadge::printpreview');
$routes->post('/PrintBadge/printpreview', 'PrintBadge::printpreview');

$routes->get('/PrintBadge/printpreviewchina', 'PrintBadge::printpreviewchina');
$routes->post('/PrintBadge/printpreviewchina', 'PrintBadge::printpreviewchina');

$routes->get('/PrintBadge/korea', 'PrintBadge::korea');
$routes->post('/PrintBadge/korea', 'PrintBadge::korea');
$routes->get('/PrintBadge/china', 'PrintBadge::china');
$routes->post('/PrintBadge/china', 'PrintBadge::china');

$routes->get('/PrintBadge/printchina', 'PrintBadge::printchina');
$routes->post('/PrintBadge/printchina', 'PrintBadge::printchina');

$routes->get('/PrintBadge/printkorea', 'PrintBadge::printkorea');
$routes->post('/PrintBadge/printkorea', 'PrintBadge::printkorea');

$routes->get('/PrintBadge/printgeneral', 'PrintBadge::printgeneral');
$routes->post('/PrintBadge/printgeneral', 'PrintBadge::printgeneral');



/* $routes->get('/Smember2', 'S2_match_db::index');  
$routes->post('/Smember2', 'S2_match_db::index');   */      

$routes->get('/Smember', 'Smember::index');  
$routes->post('/Smember', 'Smember::index');  

$routes->get('/smember/crosscheck_users1', 'Smember::crosscheck_users1');  
$routes->post('/smember/crosscheck_users1', 'Smember::crosscheck_users1');  

$routes->get('/smember/crosscheck_users500', 'Smember::crosscheck_users500');  
$routes->post('/smember/crosscheck_users500', 'Smember::crosscheck_users500');  

$routes->get('/smember/crosscheck_users1000', 'Smember::crosscheck_users1000');  
$routes->post('/smember/crosscheck_users1000', 'Smember::crosscheck_users1000');  

$routes->get('/smember/crosscheck_users1500', 'Smember::crosscheck_users1500');  
$routes->post('/smember/crosscheck_users1500', 'Smember::crosscheck_users1500');

$routes->get('/smember/crosscheck_users2000', 'Smember::crosscheck_users2000');  
$routes->post('/smember/crosscheck_users2000', 'Smember::crosscheck_users2000');  

$routes->get('/smember/crosscheck_users2500', 'Smember::crosscheck_users2500');  
$routes->post('/smember/crosscheck_users2500', 'Smember::crosscheck_users2500');

$routes->get('/smember/crosscheck_users3000', 'Smember::crosscheck_users3000');  
$routes->post('/smember/crosscheck_users3000', 'Smember::crosscheck_users3000');

$routes->get('/smember/crosscheck_users3500', 'Smember::crosscheck_users3500');  
$routes->post('/smember/crosscheck_users3500', 'Smember::crosscheck_users3500');

$routes->get('/smember/crosscheck_users4000', 'Smember::crosscheck_users4000');  
$routes->post('/smember/crosscheck_users4000', 'Smember::crosscheck_users4000'); 

$routes->get('/smember/crosscheck_users4500', 'Smember::crosscheck_users4500');  
$routes->post('/smember/crosscheck_users4500', 'Smember::crosscheck_users4500');

$routes->get('/smember/crosscheck_users5000', 'Smember::crosscheck_users5000');  
$routes->post('/smember/crosscheck_users5000', 'Smember::crosscheck_users5000');   

$routes->get('/smember/crosscheck_users5500', 'Smember::crosscheck_users5500');  
$routes->post('/smember/crosscheck_users5500', 'Smember::crosscheck_users5500');

$routes->get('/smember/crosscheck_users6000', 'Smember::crosscheck_users6000');  
$routes->post('/smember/crosscheck_users6000', 'Smember::crosscheck_users6000');

$routes->get('/smember/crosscheck_users5500', 'Smember::crosscheck_users5500');  
$routes->post('/smember/crosscheck_users5500', 'Smember::crosscheck_users5500');

$routes->get('/smember/crosscheck_users6000', 'Smember::crosscheck_users6000');  
$routes->post('/smember/crosscheck_users6000', 'Smember::crosscheck_users6000');

$routes->get('/smember/crosscheck_users6500', 'Smember::crosscheck_users6500');  
$routes->post('/smember/crosscheck_users6500', 'Smember::crosscheck_users6500');

$routes->get('/smember/crosscheck_users7000', 'Smember::crosscheck_users7000');  
$routes->post('/smember/crosscheck_users7000', 'Smember::crosscheck_users7000');

$routes->get('/smember/crosscheck_users7500', 'Smember::crosscheck_users7500');  
$routes->post('/smember/crosscheck_users7500', 'Smember::crosscheck_users7500');

$routes->get('/smember/crosscheck_users8000', 'Smember::crosscheck_users8000');  
$routes->post('/smember/crosscheck_users8000', 'Smember::crosscheck_users8000');

$routes->get('/smember/crosscheck_users8500', 'Smember::crosscheck_users8500');  
$routes->post('/smember/crosscheck_users8500', 'Smember::crosscheck_users8500');

$routes->get('/smember/crosscheck_users9000', 'Smember::crosscheck_users9000');  
$routes->post('/smember/crosscheck_users9000', 'Smember::crosscheck_users9000');

$routes->get('/smember/crosscheck_users9500', 'Smember::crosscheck_users9500');  
$routes->post('/smember/crosscheck_users9500', 'Smember::crosscheck_users9500');

$routes->get('/smember/crosscheck_users10000', 'Smember::crosscheck_users10000');  
$routes->post('/smember/crosscheck_users10000', 'Smember::crosscheck_users10000');



$routes->get('/smember/show_user', 'Smember::show_user');  
$routes->post('/smember/show_user', 'Smember::show_user');  

$routes->get('/smember/find_user_list', 'Smember::find_user_list');  
$routes->post('/smember/find_user_list', 'Smember::find_user_list');  

$routes->get('/contactsID/findID', 'contactsID::findID');  
$routes->post('/contactsID/findID', 'contactsID::findID'); 


$routes->get('/smember/reset_mesa_all', 'Smember::reset_mesa_all');  
$routes->post('/smember/reset_mesa_all', 'Smember::reset_mesa_all');  

$routes->get('/smember/reset_china_all', 'Smember::reset_china_all');  
$routes->post('/smember/reset_china_all', 'Smember::reset_china_all'); 


$routes->get('/smember/set_china_users', 'Smember::set_china_users');  
$routes->post('/smember/set_china_users', 'Smember::set_china_users'); 

 
$routes->get('/smember/set_mesa_users', 'Smember::set_mesa_users');  
$routes->post('/smember/set_mesa_users', 'Smember::set_mesa_users'); 

$routes->get('/smember/preview_add_to_database', 'Smember::preview_add_to_database');  
$routes->post('/smember/preview_add_to_database', 'Smember::preview_add_to_database'); 

$routes->get('/smember/add_to_database', 'Smember::add_to_database');  
$routes->post('/smember/add_to_database', 'Smember::add_to_database'); 

$routes->get('/smember/write_mailchimp_with_Chinese', 'Smember::write_mailchimp_with_Chinese');  
$routes->post('/smember/write_mailchimp_with_Chinese', 'Smember::write_mailchimp_with_Chinese'); 


$routes->get('/smember/set_korea_users', 'Smember::set_korea_users');  
$routes->post('/smember/set_korea_users', 'Smember::set_korea_users');  

$routes->get('/smember/crosscheck_users_all', 'Smember::crosscheck_users_all');  
$routes->post('/smember/crosscheck_users_all', 'Smember::crosscheck_users_all');  

$routes->get('/smember/recheck_crosscheck_all', 'Smember::recheck_crosscheck_all');  
$routes->post('/smember/recheck_crosscheck_all', 'Smember::recheck_crosscheck_all');  


$routes->get('/Filter_name', 'Filter_name::index');
$routes->post('/Filter_name', 'Filter_name::index');

$routes->get('/Filter_name/filter', 'Filter_name::filter');
$routes->post('/Filter_name/filter', 'Filter_name::filter');

$routes->get('/testprintform', 'testprintform::index');
$routes->post('/testprintform', 'testprintform::index');
$routes->get('testprintform/checkuser', 'testprintform::checkuser');
$routes->post('testprintform/checkuser', 'testprintform::checkuser');


$routes->get('/expo/contact', 'Expo::contact1337');          
$routes->post('/expo/contact', 'Expo::contact1337');
$routes->get('/Expo/duplicate', 'Expo::duplicate');          
$routes->post('/Expo/duplicate', 'Expo::duplicate');

$routes->get('/expo/list_expo_entries_mesa', 'Expo::list_expo_entries_mesa'); 
$routes->get('/expo/list_expo_entries_suzhou', 'Expo::list_expo_entries_suzhou'); 
$routes->get('/expo/list_expo_entries_shenzhen', 'Expo::list_expo_entries_shenzhen'); 
$routes->get('/expo/list_expo_entries_shanghai', 'Expo::list_expo_entries_shanghai');
$routes->get('/expo/list_expo_entries_korea', 'Expo::list_expo_entries_korea');

$routes->get('/expo/list_expo_entries_china', 'Expo::list_expo_entries_china'); 

$routes->get('/presentations', 'Presentations::index');
$routes->post('/presentations', 'Presentations::index');

$routes->get('/presentations/general', 'Presentations::general');
$routes->post('/presentations/general', 'Presentations::general');

$routes->get('/presentations/mesa', 'Presentations::mesa');
$routes->post('/presentations/mesa', 'Presentations::mesa');

$routes->get('/presentations/china', 'Presentations::china');
$routes->post('/presentations/china', 'Presentations::china');

$routes->get('/presentations/korea', 'Presentations::korea');
$routes->post('/presentations/korea', 'Presentations::korea');

$routes->get('/presentations/authors', 'Presentations::authors');
$routes->post('/presentations/authors', 'Presentations::authors');

$routes->get('/presentations/attendance', 'Presentations::attendance');
$routes->post('/presentations/attendance', 'Presentations::attendance');

$routes->get('/Asiaguestchinese/company123', 'Asiaguestchinese::company123');
$routes->post('/Asiaguestchinese/company123', 'Asiaguestchinese::company123');

$routes->get('/Asiaguestchinese/multigrid', 'Asiaguestchinese::multigrid');
$routes->post('/Asiaguestchinese/multigrid', 'Asiaguestchinese::multigrid');

$routes->get('/Asiaguestchinese/contact585442', 'Asiaguestchinese::contact585442');
$routes->post('/Asiaguestchinese/contact585442', 'Asiaguestchinese::contact585442');

$routes->get('/Asiaguestchinese/guest_list/?id=(:any)', 'Asiaguestchinese::guest_list');
$routes->post('/Asiaguestchinese/guest_list/?id=(:any)', 'Asiaguestchinese::guest_list');

$routes->get('/Asiaguestchinese/guest_list', 'Asiaguestchinese::guest_list');
$routes->post('/Asiaguestchinese/guest_list', 'Asiaguestchinese::guest_list');

$routes->get('/Asiaguestchinese/guest_list2', 'Asiaguestchinese::guest_list2');
$routes->post('/Asiaguestchinese/guest_list2', 'Asiaguestchinese::guest_list2');

$routes->get('/Chinaguest/guest_list/?id=(:any)', 'Chinaguest::guest_list');
$routes->post('/Chinaguest/guest_list/?id=(:any)', 'Chinaguest::guest_list');

$routes->get('/Chinaguest/guest_list', 'Chinaguest::guest_list');
$routes->post('/Chinaguest/guest_list', 'Chinaguest::guest_list');

$routes->get('/Guest/guest_list/?id=(:any)', 'Guest::guest_list');
$routes->post('/Guest/guest_list/?id=(:any)', 'Guest::guest_list');

$routes->get('/Guest/guest_list', 'Guest::guest_list');
$routes->post('/Guest/guest_list', 'Guest::guest_list');

$routes->get('/Guest/companychina34556672', 'Guest::companychina34556672');
$routes->post('/Guest/companychina34556672', 'Guest::companychina34556672');

$routes->get('/Guest/companykorea2346878438', 'Guest::companykorea2346878438');
$routes->post('/Guest/companykorea2346878438', 'Guest::companykorea2346878438');

$routes->get('/Guest/Chinastats', 'Guest::stats397927');
$routes->post('/Guest/Chinastats', 'Guest::stats397927');

$routes->get('/Guest/Koreastats', 'Guest::statsk397927');
$routes->post('/Guest/Koreastats', 'Guest::statsk397927');

$routes->get('/Guest/Guestcrudchina', 'Guest::Guestcrudchina');
$routes->post('/Guest/Guestcrudchina', 'Guest::Guestcrudchina');

$routes->get('/Guest/Guestcrudkorea', 'Guest::Guestcrudkorea');
$routes->post('/Guest/Guestcrudkorea', 'Guest::Guestcrudkorea');

$routes->get('/Guest/statsraw', 'Guest::stats397927raw');
$routes->post('/Guest/statsraw', 'Guest::stats397927raw');


$routes->get('/Koreaguest/guest_list', 'Koreaguest::guest_list');
$routes->post('/Koreaguest/guest_list', 'Koreaguest::guest_list');

$routes->get('/Asiaguestchinese/guest_listtest', 'Asiaguestchinese::guest_listtest');
$routes->post('/Asiaguestchinese/guest_listtest', 'Asiaguestchinese::guest_listtest');

$routes->get('/Asiaguestchinese/guest_listtest2', 'Asiaguestchinese::guest_listtest2');
$routes->post('/Asiaguestchinese/guest_listtest2', 'Asiaguestchinese::guest_listtest2');

$routes->get('/ViewTest', 'ViewTest::index');  
$routes->post('/ViewTest', 'ViewTest::index');  

$routes->get('/Check', 'ContactCheck::check');  
$routes->post('/Check', 'ContactCheck::check');
$routes->post('ContactCheck/do_upload', 'ContactCheck::do_upload');

$routes->get('/AuthorCheck', 'Authorpopulate::check');  
$routes->post('/AuthorCheck', 'Authorpopulate::check');
$routes->post('Authorpopulate/do_upload', 'Authorpopulate::do_upload');

$routes->get('/EXPOpopulate', 'EXPOpopulate::check');  
$routes->post('/EXPOpopulate', 'EXPOpopulate::check');
$routes->post('EXPOpopulate/populate', 'EXPOpopulate::populate');

$routes->get('/EXPOpopulate/getsecretkey', 'EXPOpopulate::getsecretkey');  
$routes->post('/EXPOpopulate/getsecretkey', 'EXPOpopulate::getsecretkey');

$routes->get('/Generalform', 'Generalform::index');  
$routes->post('/Generalform', 'Generalform::index'); 

$routes->get('/Generalform/certs', 'Generalform::certs');  
$routes->post('/Generalform/certs', 'Generalform::certs');

$routes->get('/GeneralCert', 'GeneralCert::index');  
$routes->post('/GeneralCert', 'GeneralCert::index'); 

$routes->get('/GeneralCert/certificates', 'GeneralCert::certificates');  
$routes->post('/GeneralCert/certificates', 'GeneralCert::certificates');  

$routes->get('/Jotformpost', 'Jotformpost::postguest');  
$routes->post('/Jotformpost', 'Jotformpost::postguest');  

$routes->get('/emailcheck', 'emailcheck::index');  
$routes->post('/emailcheck', 'emailcheck::index'); 

$routes->get('/emailcheck/emailcheck', 'emailcheck::emailcheck');  
$routes->post('/emailcheck/emailcheck', 'emailcheck::emailcheck');

$routes->get('/test/testarray', 'test::testarray');  
$routes->post('/test/testarray', 'test::testarray');

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
