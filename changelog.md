# 07/2025 Refactor Change Log

## Changes from previous version to current version
Split the original monolithic FullNameParser (code.old/FullNameParser.php) into two modernized versions.

### FullNameParser.php (PHP 8.2+/8.3+):
   - Uses enums, readonly classes, and typed properties.
   - Introduces an immutable ParsedName value object and a ParserConfig for configuration.
   - Improved API: parseQuick, parse, and better error handling via NameParsingException.
   - Extraction logic modularized (nickname, suffixes, prefix, names).
   - More robust handling of compound surnames, suffixes, and nicknames.
   - Modern formatting and case handling (preserveCase option, internal caps detection).
   - Example usage and CLI test cases included.

### FullNameParserLegacy.php (PHP 7.4+):
   - Backports the new design for older PHP versions (no enums/readonly, uses public properties).
   - API and extraction logic matches new version as closely as possible.
   - Maintains compatibility with legacy code and usage patterns.

## Major design changes:
   - Separation of concerns: parsing logic, configuration, and result representation are now distinct.
   - Improved extensibility and maintainability.
   - Exception-based error handling replaces silent failures.
   - Static factory methods for quick parsing.

## Feature improvements:
   - More comprehensive handling of prefixes, suffixes, and compound surnames.
   - Nickname extraction improved (parentheses and quotes).
   - Case formatting and normalization options.
   - CLI test harness for quick validation.
   - Legacy code (FullNameParser.php) retained for reference and comparison.