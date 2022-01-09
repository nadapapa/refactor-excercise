<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\RepositoryInterface;
use App\Interfaces\ViewInterface;
use App\Models\Order;

class OrderController
{
    private RepositoryInterface $orderRepository;
    private RepositoryInterface $stockRepository;
    private ViewInterface $view;

    public function __construct(
        RepositoryInterface $orderRepository,
        RepositoryInterface $stockRepository,
        ViewInterface $view,
    ) {
        $this->orderRepository = $orderRepository;
        $this->stockRepository = $stockRepository;
        $this->view = $view;
    }

    public function getFulfillableOrders(): array
    {
        $orders = $this->orderRepository->getAll();
        $stocks = $this->stockRepository->getAll();

        usort($orders, function (Order $a, Order $b) {
            $pc = -1 * ($a->getPriority() <=> $b->getPriority());
            return $pc == 0 ? $a->getCreatedAt() <=> $b->getCreatedAt() : $pc;
        });

        $filteredOrders = array_filter($orders, function(Order $order) use ($stocks) {
            if (isset($stocks[$order->getProductId()])) {
                return $stocks[$order->getProductId()] >= $order->getQuantity();
            }

            return false;
        });
        $filteredOrders = array_values($filteredOrders);

        $this->view->setData($filteredOrders)->render();

        return $filteredOrders;
    }
}
