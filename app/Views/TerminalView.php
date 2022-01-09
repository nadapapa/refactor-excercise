<?php

declare(strict_types=1);

namespace App\Views;

use App\Interfaces\ViewInterface;

class TerminalView implements ViewInterface
{
    private const HEADERS = [
        'product_id',
        'quantity',
        'priority',
        'created_at',
    ];

    private array $orders;

    public function setData(array $orders): self
    {
        $this->orders = $orders;
        return $this;
    }

    public function render(): void
    {
        foreach (static::HEADERS as $header) {
            echo str_pad($header, 20);
        }
        echo "\n";

        foreach (static::HEADERS as $header) {
            echo str_repeat('=', 20);
        }
        echo "\n";

        /** @var \App\Models\Order $order */
        foreach ($this->orders as $order) {
            foreach (static::HEADERS as $header) {
                $attribute = $order->getAttribute($header);

                if ($header === 'priority') {
                    $attribute = $order->getPriorityName();
                }

                echo str_pad((string)$attribute, 20);
            }
            echo "\n";
        }
    }
}
