<?php

declare(strict_types=1);

namespace Tests;

use App\Interfaces\ViewInterface;
use App\Models\Order;

class OrderControllerTest extends TestCase
{
    /**
     * @dataProvider stockDataProvider
     * @test
     */
    public function filterFulfillableOrders(string $stockData, callable $assertions): void
    {
        $orderDataStrategy = new \App\Repositories\OrderRepository();
        $stockDataStrategy = new \App\Repositories\StockRepository(['', $stockData]);
        $mockView = new class implements ViewInterface {
            public array $data = [];

            public function setData(array $data): ViewInterface
            {
                $this->data = $data;
                return $this;
            }

            public function render(): void
            {
            }
        };

        $controller = new \App\Controllers\OrderController($orderDataStrategy, $stockDataStrategy, $mockView);
        $controller->getFulfillableOrders();

        /** @var Order[] $data */
        $data = $mockView->data;

        $assertions($data);
    }

    /**
     * @test
     */
    public function filterFulfillableOrdersInvalidArgumentNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $orderDataStrategy = new \App\Repositories\OrderRepository();
        $stockDataStrategy = new \App\Repositories\StockRepository([]);
        $mockView = new class implements ViewInterface {
            public array $data = [];

            public function setData(array $data): ViewInterface
            {
                $this->data = $data;
                return $this;
            }

            public function render(): void
            {
            }
        };

        $controller = new \App\Controllers\OrderController($orderDataStrategy, $stockDataStrategy, $mockView);
        $controller->getFulfillableOrders();
    }

    /**
     * @test
     */
    public function filterFulfillableOrdersInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        $orderDataStrategy = new \App\Repositories\OrderRepository();
        $stockDataStrategy = new \App\Repositories\StockRepository(['', '']);
        $mockView = new class implements ViewInterface {
            public array $data = [];

            public function setData(array $data): ViewInterface
            {
                $this->data = $data;
                return $this;
            }

            public function render(): void
            {
            }
        };

        $controller = new \App\Controllers\OrderController($orderDataStrategy, $stockDataStrategy, $mockView);
        $controller->getFulfillableOrders();
    }

    public function stockDataProvider(): array
    {
        return [
            [
                '{"1":2}',
                function (array $data) {
                    $this->assertCount(2, $data);
                    $this->assertEquals(1, $data[0]->getProductId());
                    $this->assertEquals(2, $data[0]->getQuantity());
                    $this->assertEquals(3, $data[0]->getPriority());
                    $this->assertEquals('2021-03-25 14:51:47', $data[0]->getCreatedAt());

                    $this->assertEquals(1, $data[1]->getProductId());
                    $this->assertEquals(1, $data[1]->getQuantity());
                    $this->assertEquals(1, $data[1]->getPriority());
                    $this->assertEquals('2021-03-25 19:08:22', $data[1]->getCreatedAt());
                }
            ],
            [
                '{"1":8,"2":4,"3":5}',
                function (array $data) {
                    $this->assertCount(10, $data);
                    $this->assertEquals(3, $data[0]->getProductId());
                    $this->assertEquals(1, $data[1]->getProductId());
                    $this->assertEquals(2, $data[2]->getProductId());
                    $this->assertEquals(1, $data[3]->getProductId());
                    $this->assertEquals(3, $data[4]->getProductId());
                    $this->assertEquals(1, $data[5]->getProductId());
                    $this->assertEquals(2, $data[6]->getProductId());
                    $this->assertEquals(2, $data[7]->getProductId());
                    $this->assertEquals(3, $data[8]->getProductId());
                    $this->assertEquals(1, $data[9]->getProductId());
                }
            ],
            [
                '{"1":2,"2":3,"3":1}',
                function (array $data) {
                    $this->assertCount(5, $data);
                    $this->assertEquals(1, $data[0]->getProductId());
                    $this->assertEquals(2, $data[1]->getProductId());
                    $this->assertEquals(3, $data[2]->getProductId());
                    $this->assertEquals(2, $data[3]->getProductId());
                    $this->assertEquals(1, $data[4]->getProductId());
                }
            ],
        ];
    }

    /**
     * @test
     */
    public function renderTerminalOutput(): void
    {
        $terminalView = new \App\Views\TerminalView();
        $terminalView->setData([
            new Order(1, 2, 3, '2022-01-09'),
            new Order(1, 2, 2, '2022-01-09'),
            new Order(1, 2, 1, '2022-01-09'),
        ]);
        $terminalView->render();

        $this->expectOutputString(<<<EOF
product_id          quantity            priority            created_at          
================================================================================
1                   2                   high                2022-01-09          
1                   2                   medium              2022-01-09          
1                   2                   low                 2022-01-09          

EOF
        );
    }
}
