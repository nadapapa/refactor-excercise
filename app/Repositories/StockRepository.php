<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;

class StockRepository implements RepositoryInterface
{
    private array $arguments;

    public function __construct(array $argv)
    {
        $this->arguments = $argv;
    }

    public function getAll(): array
    {
        if (count($this->arguments) != 2) {
            echo 'Ambiguous number of parameters!';
            return [];
        }

        if (($stock = json_decode($this->arguments[1], true)) == null) {
            return [];
        }

        return $stock;
    }
}
