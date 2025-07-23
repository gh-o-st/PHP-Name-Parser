<?php
declare(strict_types=1);

class NameParsingException extends InvalidArgumentException
{
    public static function emptyName(): self
    {
        return new self('The provided name cannot be empty.');
    }

    public static function invalidFormat(string $message): self
    {
        return new self("Invalid name format: {$message}");
    }
}