<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use BackedEnum;
use Savks\Negotiator\Support\DTO\Value;
use Stringable;

final class Record
{
    protected array $keys = [];

    /**
     * @var list<array{string|int|Stringable|BackedEnum, Value}>
     */
    protected array $entries = [];

    public function set(string|int|Stringable|BackedEnum $key, Value $value): self
    {
        $index = array_search($key, $this->keys, true);

        if ($index === false) {
            $this->entries[] = [$key, $value];

            $this->keys[] = $key;
        } else {
            $this->entries[$index] = [$key, $value];
        }

        return $this;
    }

    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @param list<array{string|int|Stringable|BackedEnum, Value}> $entries
     */
    public static function fromEntries(array $entries): self
    {
        $record = new self();

        foreach ($entries as [$key, $value]) {
            $record->set($key, $value);
        }

        return $record;
    }
}
