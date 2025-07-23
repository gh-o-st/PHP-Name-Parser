<?php
declare(strict_types=1);

/**
 * Configuration for name parsing behavior
 */
class ParserConfig
{
    public $strictMode;
    public $preserveCase;
    public $maxLength;
    public $order;

    public function __construct($strictMode = false, $preserveCase = false, $maxLength = 1000, $order = 'first-last')
    {
        $this->strictMode = $strictMode;
        $this->preserveCase = $preserveCase;
        $this->maxLength = $maxLength;
        $this->order = $order;
    }
    
    /**
     * Getters for configuration options
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }
    public function isPreserveCase(): bool
    {
        return $this->preserveCase;
    }
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }
    public function getOrder(): string
    {
        return $this->order;
    }
}