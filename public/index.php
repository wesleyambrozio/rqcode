<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

session_name('central_saas_session');
session_start();

$router = new Router();

$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@authenticate');
$router->post('/logout', 'AuthController@logout');

$router->get('/', 'DashboardController@index', true);
$router->get('/vendedores', 'VendorController@index', true);
$router->post('/vendedores', 'VendorController@store', true);
$router->get('/sistemas', 'SystemController@index', true);
$router->post('/sistemas', 'SystemController@store', true);
$router->get('/vendas', 'SaleController@index', true);
$router->post('/vendas', 'SaleController@store', true);
$router->get('/financeiro', 'FinanceController@index', true);
$router->post('/financeiro', 'FinanceController@store', true);
$router->post('/financeiro/liquidar', 'FinanceController@settle', true);
$router->get('/suporte', 'SupportController@index', true);
$router->post('/suporte', 'SupportController@store', true);
$router->get('/integracoes', 'IntegrationController@index', true);
$router->post('/integracoes', 'IntegrationController@store', true);
$router->get('/relatorios', 'ReportController@index', true);
$router->get('/configuracoes', 'SettingsController@index', true);

if (!Auth::check() && !in_array(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), ['/login'], true)) {
    redirect('/login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
