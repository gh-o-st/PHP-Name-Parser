<?php
declare(strict_types=1);

class ExtractionContext
{
    public string $prefix = '';
    public string $firstName = '';
    public string $middleName = '';
    public string $lastName = '';
    public string $lineageSuffix = '';
    public array $professionalSuffixes = [];
    public string $nickname = '';

    public function __construct(
        public string $workingName
    ) {}
}
