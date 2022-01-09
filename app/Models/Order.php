<?php

declare(strict_types=1);

namespace App\Models;

class Order extends AbstractModel
{
    protected int $productId;
    protected int $quantity;
    protected int $priority;
    protected string $createdAt;

    private const PRIORITY_LOW = 'low';
    private const PRIORITY_MEDIUM = 'medium';
    private const PRIORITY_HIGH = 'high';

    public function __construct(
        int $productId,
        int $quantity,
        int $priority,
        string $createdAt,
    ) {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->priority = $priority;
        $this->createdAt = $createdAt;
    }

    public function getPriorityName(): string
    {
        return match ($this->priority) {
            1 => static::PRIORITY_LOW,
            2 => static::PRIORITY_MEDIUM,
            3 => static::PRIORITY_HIGH,
        };
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
