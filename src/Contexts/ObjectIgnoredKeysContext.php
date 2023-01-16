<?php

namespace Savks\Negotiator\Contexts;

use Savks\PhpContexts\Context;

class ObjectIgnoredKeysContext extends Context
{
    public function __construct(public array $keys = [])
    {
    }

    public function push(array $keys): static
    {
        foreach ($keys as $key) {
            $this->keys[] = $key;
        }

        $this->keys = \array_keys(
            \array_flip($this->keys)
        );

        return $this;
    }

    public function includes(string $key): bool
    {
        return \in_array($key, $this->keys, true);
    }
}
