<?php

declare(strict_types=1);

namespace App\Models;

abstract class AbstractModel
{
    public function getAttribute(string $key): mixed
    {
        $attribute = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))));

        return $this->{$attribute};
    }
}
