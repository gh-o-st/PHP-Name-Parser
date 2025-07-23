<?php
// Refactored for 8.2+

declare(strict_types=1);

require_once __DIR__ . '/ParserConfig.php';
require_once __DIR__ . '/ParsedName.php';
require_once __DIR__ . '/ExtractionContext.php';


/**
 * Split a full name into its constituent parts
 *   - prefix/salutation (Mr. Mrs. Dr. etc)
 *   - given/first name
 *   - middle name/initial(s)
 *   - surname (last name)
 *   - surname base (last name without compounds)
 *   - surname compounds (only the compounds)
 *   - suffix (II, PhD, Jr. etc)
 *
 * Author: Josh Fraser
 *
 * Contribution from Clive Verrall www.cliveverrall.com February 2016
 * 
 * // other contributions: 
 * //   - eric willis [list of honorifics](http://notes.ericwillis.com/2009/11/common-name-prefixes-titles-and-honorifics/)
 * //   - `TomThak` for raising issue #16 and providing [wikepedia resource](https://cs.wikipedia.org/wiki/Akademick%C3%BD_titul)
 * //   - `atla5` for closing the issue.
*/

enum NameComponent: string
{
    case PREFIX = 'prefix';
    case FIRST_NAME = 'firstName';
    case MIDDLE_NAME = 'middleName';
    case LAST_NAME = 'lastName';
    case SUFFIX = 'suffix';
    case NICKNAME = 'nickname';
}

final class FullNameParser
{
    private const PREFIXES = [
        'mr', 'mister', 'master', 'mrs', 'missus', 'ms', 'miss', 'dr', 'rev', 
        'fr', 'sr', 'prof', 'sir', 'honorable', 'pres', 'gov', 'governor', 
        'officer', 'ofc', 'msgr', 'br', 'supt', 'rep', 'sen', 'amb', 'treas', 
        'sec', 'pvt', 'cpl', 'sgt', 'adm', 'maj', 'capt', 'cmdr', 'lt', 'col', 
        'gen', 'the'
    ];

    private const LINEAGE_SUFFIXES = [
        'i', 'ii', 'iii', 'iv', 'v', '1st', '2nd', '3rd', '4th', '5th', 
        'senior', 'junior', 'jr', 'sr'
    ];

    private const COMPOUND_SURNAMES = [
        'da', 'de', 'del', 'della', 'dem', 'den', 'der', 'di', 'du', 'het', 
        'la', 'onder', 'op', 'pietro', 'st', "'t", 'ten', 'ter', 'van', 
        'vanden', 'vere', 'von'
    ];

    private const PROFESSIONAL_SUFFIXES = [
        'phd', 'ph.d.', 'md', 'm.d.', 'jd', 'j.d.', 'mba', 'm.b.a.', 'ma', 
        'ms', 'bs', 'ba', 'esq', 'pe', 'rn', 'cpa', 'dds', 'd.d.s.', 'dvm', 
        'pharmd', 'edd', 'psyd', 'llm', 'll.m', 'llb', 'll.b', 'bsc', 'msc',
        // Add more as needed...
    ];

    public function __construct(
        private readonly ParserConfig $config = new ParserConfig()
    ) {}

    /**
     * Kept for backward compatibility with legacy code
     * @deprecated Use `parse` method instead
     */
    public function parse_name(string $fullName): ParsedName
    {
        return $this->parse($fullName);
    }

    /**
     * Parse a full name string into components
     */
    public function parse(string $fullName): ParsedName
    {
        $this->validateInput($fullName);
        $cleanName = $this->cleanInput($fullName);
        $extractionContext = new ExtractionContext($cleanName);
        $this->extractNickname($extractionContext);
        $this->extractProfessionalSuffixes($extractionContext);
        $this->extractLineageSuffix($extractionContext);
        $this->extractPrefix($extractionContext);
        $this->extractNames($extractionContext);
        return $this->buildResult($extractionContext);
    }

    /**
     * Static factory method for simple usage
     */
    public static function parseQuick(string $fullName): ParsedName
    {
        return (new self())->parse($fullName);
    }

    private function validateInput(string $fullName): void
    {
        if (trim($fullName) === '') {
            throw NameParsingException::emptyName();
        }
        if (strlen($fullName) > $this->config->getMaxLength()) {
            throw new NameParsingException(
                "Name exceeds maximum length of {$this->config->getMaxLength()} characters"
            );
        }
    }

    private function cleanInput(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function extractNickname(ExtractionContext $context): void
    {
        if (preg_match('/[\("]([^)\"]+)[\)"]/', $context->workingName, $matches)) {
            $context->nickname = trim($matches[1]);
            $context->workingName = str_replace($matches[0], '', $context->workingName);
            $context->workingName = trim($context->workingName);
        }
    }

    private function extractProfessionalSuffixes(ExtractionContext $context): void
    {
        $found = [];
        foreach (self::PROFESSIONAL_SUFFIXES as $suffix) {
            $pattern = '/[,\s]*\b' . preg_quote($suffix, '/') . '\b\.?/i';
            if (preg_match($pattern, $context->workingName, $matches)) {
                $found[] = trim($matches[0], ', ');
                $context->workingName = str_replace($matches[0], '', $context->workingName);
            }
        }
        if (!empty($found)) {
            $context->professionalSuffixes = $found;
            $context->workingName = trim($context->workingName, ', ');
        }
    }

    private function extractLineageSuffix(ExtractionContext $context): void
    {
        $words = $this->splitIntoWords($context->workingName);
        if (count($words) > 1) {
            $lastWord = $this->normalizeWord(end($words));
            if (in_array($lastWord, self::LINEAGE_SUFFIXES, true)) {
                $context->lineageSuffix = array_pop($words);
                $context->workingName = implode(' ', $words);
            }
        }
    }

    private function extractPrefix(ExtractionContext $context): void
    {
        $words = $this->splitIntoWords($context->workingName);
        $prefixes = [];
        while (!empty($words) && $this->isPrefix($words[0])) {
            $prefixes[] = array_shift($words);
        }
        if (!empty($prefixes)) {
            $context->prefix = implode(' ', $prefixes);
            $context->workingName = implode(' ', $words);
        }
    }

    private function extractNames(ExtractionContext $context): void
    {
        $words = $this->splitIntoWords($context->workingName);
        if (empty($words)) {
            return;
        }
        if ($this->config->getOrder() === 'last-first') {
            // Eastern order: Surname GivenName [MiddleName]
            $context->lastName = array_shift($words) ?? '';
            while (!empty($words) && $this->isCompoundSurname($context->lastName)) {
                $context->lastName .= ' ' . array_shift($words);
            }
            if (!empty($words)) {
                $context->firstName = array_shift($words);
                if (!empty($words)) {
                    $context->middleName = implode(' ', $words);
                }
            }
        } else {
            // Western order: GivenName [MiddleName] Surname
            $context->lastName = array_pop($words) ?? '';
            while (!empty($words) && $this->isCompoundSurname(end($words))) {
                $context->lastName = array_pop($words) . ' ' . $context->lastName;
            }
            if (!empty($words)) {
                $context->firstName = array_shift($words);
                if (!empty($words)) {
                    $context->middleName = implode(' ', $words);
                }
            }
        }
    }

    private function buildResult(ExtractionContext $context): ParsedName
    {
        $allSuffixes = array_filter([
            $context->lineageSuffix,
            ...$context->professionalSuffixes
        ]);
        return new ParsedName(
            $this->formatName($context->prefix),
            $this->formatName($context->firstName),
            $this->formatName($context->middleName),
            $this->formatName($context->lastName),
            implode(', ', $allSuffixes),
            $this->formatName($context->nickname)
        );
    }

    private function splitIntoWords(string $text): array
    {
        return array_filter(
            preg_split('/\s+/', trim($text)) ?: [],
            fn(string $word) => $word !== ''
        );
    }

    private function normalizeWord(string $word): string
    {
        return strtolower(str_replace('.', '', $word));
    }

    private function isPrefix(string $word): bool
    {
        return in_array($this->normalizeWord($word), self::PREFIXES, true);
    }

    private function isCompoundSurname(string $word): bool
    {
        return in_array(strtolower($word), self::COMPOUND_SURNAMES, true);
    }

    private function formatName(string $name): string
    {
        if ($this->config->isPreserveCase() || empty($name)) {
            return $name;
        }
        if (str_contains($name, '-')) {
            return implode('-', array_map(
                fn(string $part) => $this->formatSingleName($part),
                explode('-', $name)
            ));
        }

        // Handle compound surnames (e.g., van der Berg)
        $words = explode(' ', $name);
        foreach ($words as &$word) {
            if (in_array(strtolower($word), self::COMPOUND_SURNAMES, true)) {
                $word = strtolower($word);
            } else {
                $word = $this->formatSingleName($word);
            }
        }
        return implode(' ', $words);
    }

    private function formatSingleName(string $name): string
    {
        if (empty($name)) {
            return '';
        }
        if ($this->hasInternalCaps($name)) {
            return $name;
        }
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function hasInternalCaps(string $word): bool
    {
        if (strlen($word) <= 1) {
            return false;
        }
        return ctype_upper($word[0]) && 
               preg_match('/[A-Z]/', substr($word, 1)) === 1;
    }
}

