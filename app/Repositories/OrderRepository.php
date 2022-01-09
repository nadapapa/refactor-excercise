<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Order;

class OrderRepository implements RepositoryInterface
{
    public function getAll(): array
    {
        $orders = [];

        $row = 0;
        $handle = fopen('orders.csv', 'r');

        if ($handle !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $row++;

                if ($row === 1) {
                    continue;
                }

                $order = new Order(
                    (int)$data[0],
                    (int)$data[1],
                    (int)$data[2],
                    $data[3],
                );

                $orders[] = $order;
            }
            fclose($handle);
        }

        return $orders;
    }
}
