<?php
declare(strict_types=1);

namespace App\Interfaces;

interface ViewInterface
{
    public function setData(array $data): ViewInterface;
    public function render(): void;
}
