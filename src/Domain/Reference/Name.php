<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use App\Domain\Exception\InvalidArgumentException;

class Name
{
    private const MIN_NAME_LENGTH = 5;
    private const MAX_NAME_LENGTH = 255;

    private string $value;

    private function __construct(string $value)
    {
        if (self::MIN_NAME_LENGTH > strlen($value)) {
            throw new InvalidArgumentException(sprintf('Name "%s" is to short.', $value));
        }

        if (self::MAX_NAME_LENGTH < strlen($value)) {
            throw new InvalidArgumentException(sprintf('Name "%s" is to long.', $value));
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function asString(): string
    {
        return $this->value;
    }
}
