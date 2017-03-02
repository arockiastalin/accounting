<?php

declare(strict_types = 1);

namespace byrokrat\accounting\Sie4\Parser;

/**
 * @covers \byrokrat\accounting\Sie4\Parser\ParserFactory
 */
class ParserFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateParser()
    {
        $this->assertInstanceOf(
            Parser::CLASS,
            (new ParserFactory)->createParser()
        );
    }
}
