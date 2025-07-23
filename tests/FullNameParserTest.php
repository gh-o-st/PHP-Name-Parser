<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/FullNameParser.php';
// If ParsedName, NameParsingException, ParserConfig are separate files, add require_once for each

class FullNameParserTest extends TestCase
{
    public function testBasicParsing()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Dr. John Michael Smith Jr., PhD');
        $this->assertEquals([
            'prefix' => 'Dr.',
            'firstName' => 'John',
            'middleName' => 'Michael',
            'lastName' => 'Smith',
            'suffix' => 'Jr., PhD',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testCompoundSurname()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Prof. Elizabeth van der Berg III');
        $this->assertEquals([
            'prefix' => 'Prof.',
            'firstName' => 'Elizabeth',
            'middleName' => '',
            'lastName' => 'van der Berg',
            'suffix' => 'III',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testHyphenatedLastName()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Mary Jane Watson-Parker');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Mary',
            'middleName' => 'Jane',
            'lastName' => 'Watson-Parker',
            'suffix' => '',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testNicknameExtraction()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Michael "Mike" O\'Connor');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Michael',
            'middleName' => '',
            'lastName' => "O'Connor",
            'suffix' => '',
            'nickname' => 'Mike',
        ], $parsed->toArray());
    }

    public function testSuffixesMultiple()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Ms. Sarah J. Thompson, MD, PhD');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Sarah',
            'middleName' => 'J.',
            'lastName' => 'Thompson',
            'suffix' => 'PhD, MD, Ms.',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testEmptyNameThrows()
    {
        $this->expectException(NameParsingException::class);
        $parser = new FullNameParser();
        $parser->parse('   ');
    }

    public function testMaxLengthThrows()
    {
        $this->expectException(NameParsingException::class);
        $config = new ParserConfig(maxLength: 10);
        $parser = new FullNameParser($config);
        $parser->parse('This name is definitely longer than ten characters');
    }

    public function testPreserveCaseOption()
    {
        $config = new ParserConfig(preserveCase: true);
        $parser = new FullNameParser($config);
        $parsed = $parser->parse('mcdonald macElroy');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'mcdonald',
            'middleName' => '',
            'lastName' => 'macElroy',
            'suffix' => '',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testStrictModeOption()
    {
        $config = new ParserConfig(strictMode: true);
        $parser = new FullNameParser($config);
        $parsed = $parser->parse('Dr. John Smith');
        $this->assertEquals('Dr. John Smith', $parsed->getFullName());
    }

    public function testDisplayNameWithNickname()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('John "Johnny" Doe');
        $this->assertEquals('John Doe "Johnny"', $parsed->getDisplayName());
    }

    public function testDisplayNameWithoutNickname()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Jane Doe');
        $this->assertEquals('Jane Doe', $parsed->getDisplayName());
    }

    public function testSingleName()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Cher');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => '',
            'middleName' => '',
            'lastName' => 'Cher',
            'suffix' => '',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testNameWithParenthesesNickname()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Robert (Bob) Smith');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Robert',
            'middleName' => '',
            'lastName' => 'Smith',
            'suffix' => '',
            'nickname' => 'Bob',
        ], $parsed->toArray());
    }

    public function testNameWithMultiplePrefixes()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Dr. Prof. John Smith');
        $this->assertEquals([
            'prefix' => 'Dr. Prof.',
            'firstName' => 'John',
            'middleName' => '',
            'lastName' => 'Smith',
            'suffix' => '',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testNameWithCompoundSurnameAndSuffix()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('Anna Maria de la Cruz Jr.');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Anna',
            'middleName' => 'Maria',
            'lastName' => 'de la Cruz',
            'suffix' => 'Jr.',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testNameWithNoSpaces()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('A');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => '',
            'middleName' => '',
            'lastName' => 'A',
            'suffix' => '',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testNameWithSuffixOnly()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('John Smith Jr.');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'John',
            'middleName' => '',
            'lastName' => 'Smith',
            'suffix' => 'Jr.',
            'nickname' => '',
        ], $parsed->toArray());
    }

    public function testNameOrderSwitch()
    {
        $name = 'Zhang Wei';

        // Default: first-last
        $parserDefault = new FullNameParser();
        $parsedDefault = $parserDefault->parse($name);
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Zhang',
            'middleName' => '',
            'lastName' => 'Wei',
            'suffix' => '',
            'nickname' => '',
        ], $parsedDefault->toArray());

        // With order: last-first
        $config = new ParserConfig(false, false, 1000,'last-first');
        $parserLF = new FullNameParser($config);
        $parsedLF = $parserLF->parse($name);
        $this->assertEquals([
            'prefix' => '',
            'firstName' => 'Wei',
            'middleName' => '',
            'lastName' => 'Zhang',
            'suffix' => '',
            'nickname' => '',
        ], $parsedLF->toArray());
    }

    public function testNicknameOnly()
    {
        $parser = new FullNameParser();
        $parsed = $parser->parse('"The Rock"');
        $this->assertEquals([
            'prefix' => '',
            'firstName' => '',
            'middleName' => '',
            'lastName' => '',
            'suffix' => '',
            'nickname' => 'The Rock',
        ], $parsed->toArray());
    }
}