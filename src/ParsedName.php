<?php
// Compatible with PHP 7.4+ and 8.3+

declare(strict_types=1);

class ParsedName
{
    public $prefix;
    public $firstName;
    public $middleName;
    public $lastName;
    public $suffix;
    public $nickname;

    public function __construct(
        $prefix = '',
        $firstName = '',
        $middleName = '',
        $lastName = '',
        $suffix = '',
        $nickname = ''
    ) {
        $this->prefix = $prefix;
        $this->firstName = $firstName;
        $this->middleName = $middleName;
        $this->lastName = $lastName;
        $this->suffix = $suffix;
        $this->nickname = $nickname;
    }

    public function toArray(): array
    {
        return [
            'prefix' => $this->prefix,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'suffix' => $this->suffix,
            'nickname' => $this->nickname,
        ];
    }

    public function getFullName(): string
    {
        $parts = array_filter([
            $this->prefix,
            $this->firstName,
            $this->middleName,
            $this->lastName,
            $this->suffix,
        ]);
        return implode(' ', $parts);
    }

    public function getDisplayName(): string
    {
        $name = trim("{$this->firstName} {$this->lastName}");
        return $this->nickname ? "{$name} \"{$this->nickname}\"" : $name;
    }
}
