<?php

declare(strict_types=1);

namespace byrokrat\accounting\Dimension;

use byrokrat\accounting\AttributableTestTrait;
use byrokrat\accounting\AttributableInterface;
use byrokrat\accounting\Exception\LogicException;

class DimensionTest extends \PHPUnit\Framework\TestCase
{
    use AttributableTestTrait;

    protected function getAttributableToTest(): AttributableInterface
    {
        return new Dimension('');
    }

    public function testGetId()
    {
        $this->assertSame(
            '1234',
            (new Dimension('1234'))->getId()
        );
    }

    public function testDescription()
    {
        $this->assertSame('foo', (new Dimension('', 'foo'))->getDescription());
    }

    public function testParent()
    {
        $parent = new Dimension('0');
        $child = new Dimension('0', '', $parent);

        $this->assertTrue($child->hasParent());

        $this->assertSame(
            $parent,
            $child->getParent()
        );

        $this->assertSame(
            [$parent],
            $child->select()->asArray()
        );
    }

    public function testNoParent()
    {
        $dim = new Dimension('0');

        $this->assertFalse($dim->hasParent());

        $this->assertSame(
            [],
            $dim->select()->asArray()
        );
    }

    public function testExceptionWhenNoParentIsSet()
    {
        $this->expectException(LogicException::CLASS);
        (new Dimension('0'))->getParent();
    }

    public function testInDimension()
    {
        $dim = new Dimension(
            '0',
            '',
            new Dimension(
                '1',
                '',
                new Dimension('2')
            )
        );

        $this->assertFalse($dim->inDimension('0'));
        $this->assertTrue($dim->inDimension('1'));
        $this->assertTrue($dim->inDimension('2'));
        $this->assertTrue($dim->inDimension(new Dimension('2')));
        $this->assertFalse($dim->inDimension('3'));
    }
}
