<?php

declare(strict_types = 1);

namespace byrokrat\accounting\Sie4;

use byrokrat\accounting\Account;
use byrokrat\accounting\Dimension;
use byrokrat\amount\Currency\SEK;

/**
 * Phpunit data providers for the SIE types defined in SieGrammar
 */
trait TypeProviderTrait
{
    /**
     * Provider for possible boolean representations
     */
    public function booleanTypeProvider()
    {
        return [
            ['0',   false],
            ['1',   true],
            ['"0"', false],
            ['"1"', true],
        ];
    }

    /**
     * Provider for possible currency representations according to rule 5.9
     */
    public function currencyTypeProvider()
    {
        return [
            ['1',      new SEK('1')],
            ['10.11',  new SEK('10.11')],
            ['10.1',   new SEK('10.10')],
            ['-1',     new SEK('-1')],
            ['"1.00"', new SEK('1')],
        ];
    }

    /**
     * Provider for possibla date representations (see rule 5.10)
     */
    public function dateTypeProvider()
    {
        return [
            ['20160722',   new \DateTimeImmutable('20160722')],
            ['"20160722"', new \DateTimeImmutable('20160722')],
            ['201607',     new \DateTimeImmutable('20160701')],
            ['2016',       new \DateTimeImmutable('20160101')],
            ['20160722',   new \DateTimeImmutable('20160722')],
        ];
    }

    /**
     * Provider for possible integer representations
     */
    public function intTypeProvider()
    {
        return [
            ['1',      1],
            ['0',      0],
            ['-1',     -1],
            ['1234',   1234],
            ['"1234"', 1234],
            ['"-1"',   -1],
        ];
    }

    /**
     * Provider for possible string representations according to rule 5.7
     */
    public function stringTypeProvider()
    {
        $validChars = array_reduce(
            array_merge([33], range(35, 126)),
            function ($carry, $char) {
                return $carry . chr($char);
            }
        );

        $values = [
            ['foo',           'foo',       'Regular string'],
            ['"foo"',         'foo',       'Quoted string'],
            ['"foo bar"',     'foo bar',   'Space inside quoted string'],
            ['"foo \\" bar"', 'foo " bar', 'Escaped quotes inside quoted string'],
            ['""',            '',          'The empty string'],
            [$validChars,     $validChars, 'Characters allowed'],
            ['åäöÅÄÖ',        'åäöÅÄÖ',    'Swedish special characters'],
        ];

        foreach ($values as list($raw, $expected)) {
            yield [iconv('UTF-8', 'CP437', $raw), $expected];
        }
    }

    /**
     * Creates an iterator of all characters NOT allowed in fields according to rule 5.7
     */
    public function stringTypeInvalidCharsProvider()
    {
        foreach (range(0, 31) as $ascii) {
            yield [chr($ascii)];
        }
        yield [chr(127)];
    }

    /**
     * Provider for possible account representations
     */
    public function accountTypeProvider()
    {
        return [
            ["#KONTO 1920 bank",               1920, 'bank',    Account\Asset::CLASS],
            ["#KONTO 1920 bank\n#KTYP 1920 S", 1920, 'bank',    Account\Debt::CLASS],
            ["#KONTO 2000 debt",               2000, 'debt',    Account\Debt::CLASS],
            ["#KONTO 3000 earning",            3000, 'earning', Account\Earning::CLASS],
            ["#KONTO 4000 cost",               4000, 'cost',    Account\Cost::CLASS],
        ];
    }
}