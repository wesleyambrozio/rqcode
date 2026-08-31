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

$router->get('/', 'PublicController@soon');
$router->post('/contato', 'PublicController@contact');
$router->get('/dashboard', 'DashboardController@index', true);
$router->get('/vendedores', 'VendorController@index', true);
$router->post('/vendedores', 'VendorController@store', true);
$router->get('/sistemas', 'SystemController@index', true);
$router->post('/sistemas', 'SystemController@store', true);
$router->get('/vendas', 'SaleController@index', true);
$router->post('/vendas', 'SaleController@store', true);
$router->get('/financeiro', 'FinanceController@index', true);
$router->post('/financeiro', 'FinanceController@store', true);
$router->post('/financeiro/plano-contas', 'FinanceController@storeAccount', true);
$router->post('/financeiro/formas-pagamento', 'FinanceController@storePaymentMethod', true);
$router->post('/financeiro/liquidar', 'FinanceController@settle', true);
$router->get('/suporte', 'SupportController@index', true);
$router->post('/suporte', 'SupportController@store', true);
$router->post('/suporte/atualizar', 'SupportController@update', true);
$router->post('/suporte/mensagem', 'SupportController@message', true);
$router->post('/suporte/filas', 'SupportController@queue', true);
$router->post('/suporte/ia', 'SupportController@aiConfig', true);
$router->get('/conhecimento', 'KnowledgeController@index', true);
$router->post('/conhecimento', 'KnowledgeController@store', true);
$router->post('/conhecimento/atualizar', 'KnowledgeController@update', true);
$router->get('/usuarios-sistemas', 'SystemUserController@index', true);
$router->post('/usuarios-sistemas', 'SystemUserController@store', true);
$router->post('/usuarios-sistemas/atualizar', 'SystemUserController@update', true);
$router->get('/integracoes', 'IntegrationController@index', true);
$router->post('/integracoes', 'IntegrationController@store', true);
$router->get('/relatorios', 'ReportController@index', true);
$router->get('/relatorios/exportar', 'ReportController@export', true);
$router->get('/configuracoes', 'SettingsController@index', true);
$router->get('/usuarios-administrativos', 'AdminUserController@index', true);
$router->post('/usuarios-administrativos', 'AdminUserController@store', true);
$router->post('/usuarios-administrativos/atualizar', 'AdminUserController@update', true);
$router->post('/usuarios-administrativos/excluir', 'AdminUserController@delete', true);
$router->get('/contabilidade', 'AccountingController@index', true);
$router->post('/contabilidade/documentos', 'AccountingController@upload', true);
$router->get('/contabilidade/documentos/download', 'AccountingController@download', true);
$router->post('/contabilidade/mensagens', 'AccountingController@message', true);
$router->post('/contabilidade/contador', 'AccountingController@createAccountant', true);
$router->get('/contabilidade/relatorio', 'AccountingController@report', true);
$router->get('/fornecedores', 'SupplierController@index', true);
$router->post('/fornecedores', 'SupplierController@store', true);
$router->get('/notas-fiscais', 'FiscalInvoiceController@index', true);
$router->post('/notas-fiscais/importar', 'FiscalInvoiceController@import', true);
$router->post('/notas-fiscais/confirmar', 'FiscalInvoiceController@confirm', true);
$router->get('/notas-fiscais/xml', 'FiscalInvoiceController@download', true);
$router->get('/impressao-3d', 'Printing3DController@index', true);
$router->post('/impressao-3d/fornecedores', 'SupplierController@store', true);
$router->post('/impressao-3d/filamentos', 'Printing3DController@filament', true);
$router->post('/impressao-3d/pecas', 'Printing3DController@product', true);
$router->post('/impressao-3d/categorias', 'Printing3DController@category', true);
$router->post('/impressao-3d/canais', 'Printing3DController@channel', true);
$router->post('/impressao-3d/producao', 'Printing3DController@production', true);
$router->post('/impressao-3d/vendas', 'Printing3DController@sale', true);
$router->get('/impressao-3d/notas-fiscais', 'FiscalInvoiceController@index', true);
$router->post('/impressao-3d/notas-fiscais/importar', 'FiscalInvoiceController@import', true);
$router->post('/impressao-3d/notas-fiscais/confirmar', 'FiscalInvoiceController@confirm', true);
$router->get('/impressao-3d/notas-fiscais/xml', 'FiscalInvoiceController@download', true);

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$publicRoutes = ['/', '/login', '/contato'];

if (!Auth::check() && !in_array($currentPath, $publicRoutes, true)) {
    redirect('/login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $currentPath);
