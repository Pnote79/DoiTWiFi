$routes->get('/', 'DashboardController::index');

$routes->get('/dashboard', 'DashboardController::index');
$routes->get('/dashboard/setting', 'DashboardController::setting');

$routes->get('/pingtest', 'ApiController::pingtest');
$routes->get('/check-conn', 'ApiController::check_conn');

$routes->get('/logout', 'DashboardController::logout');