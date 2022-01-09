<?php
declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';

$orderDataStrategy = new \App\Repositories\OrderRepository();
$stockDataStrategy = new \App\Repositories\StockRepository($argv);
$terminalView = new \App\Views\TerminalView();
$controller = new \App\Controllers\OrderController($orderDataStrategy, $stockDataStrategy, $terminalView);

$fulfillableOrders = $controller->getFulfillableOrders();
