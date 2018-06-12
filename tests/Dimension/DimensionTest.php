<?php

declare(strict_types = 1);

namespace byrokrat\accounting\Dimension;

use byrokrat\accounting\Exception\LogicException;
use byrokrat\accounting\utils;

class DimensionTest extends \PHPUnit\Framework\TestCase
{
    use utils\AttributableTestsTrait, utils\DescriptionTestsTrait;

    protected function getObjectToTest()
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

    public function testExceptionOnInvalidArgumentInDimension()
    {
        $this->expectException(LogicException::CLASS);
        (new Dimension('0'))->inDimension(0);
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