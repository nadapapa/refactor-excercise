<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use InvalidArgumentException;

class StockRepository implements RepositoryInterface
{
    private array $arguments;

    public function __construct(array $argv)
    {
        $this->arguments = $argv;
    }

    /**
     * @throws \JsonException
     */
    public function getAll(): array
    {
        if (count($this->arguments) != 2) {
            throw new InvalidArgumentException('Ambiguous number of parameters!');
        }

        return json_decode($this->arguments[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
