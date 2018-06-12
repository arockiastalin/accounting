<?php

declare(strict_types = 1);

namespace byrokrat\accounting;

use byrokrat\amount\Amount;

class QueryTest extends \PHPUnit\Framework\TestCase
{
    use utils\PropheciesTrait;

    public function testInvalidConstructorArgument()
    {
        $this->expectException(Exception\LogicException::CLASS);
        (new Query(0))->exec();
    }

    public function testAsArray()
    {
        $this->assertSame(
            [1, 2, 3],
            (new Query([1, 2, 3]))->asArray()
        );
    }

    public function testAsContainer()
    {
        $this->assertEquals(
            new Container(1, 2, 3),
            (new Query([1, 2, 3]))->asContainer()
        );
    }

    public function testAsSummary()
    {
        $trans = $this->prophesizeTransaction(new Amount('50'))->reveal();

        $this->assertEquals(
            new Amount('100'),
            (new Query([1, $trans, $trans]))->asSummary()->getOutgoingBalance()
        );
    }

    /**
     * @depends testAsArray
     */
    public function testNestedIteration()
    {
        $queryable1 = $this->prophesizeQueryable(['bar'])->reveal();
        $queryable2 = $this->prophesizeQueryable(['foo', $queryable1])->reveal();

        $query = new Query(['before', $queryable2, 'after']);

        $this->assertSame(
            ['before', $queryable2, 'foo', $queryable1, 'bar', 'after'],
            $query->asArray(),
            'Nested iteration should yield values bottom down'
        );

        $this->assertSame(
            ['before', $queryable2, 'foo', $queryable1, 'bar', 'after'],
            $query->asArray(),
            'Query should be rewindable and yield the same results the second time'
        );
    }

    /**
     * @depends testNestedIteration
     */
    public function testFilter()
    {
        $this->assertSame(
            [1, 2, 3],
            (new Query([1, $this->prophesizeQueryable([2])->reveal(), 3]))->filter('is_integer')->asArray()
        );
    }

    public function testFilterType()
    {
        $this->assertSame(
            [$query = new Query],
            (new Query([1, $query]))->filterType(Query::CLASS)->asArray()
        );
    }

    /**
     * @depends testFilter
     */
    public function testThatFilterCreatesNewQuery()
    {
        $query = new Query([1, 'A', 2]);

        $this->assertSame(
            [1, 2],
            $query->filter('is_integer')->asArray()
        );

        $this->assertSame(
            [1, 'A', 2],
            $query->asArray()
        );
    }

    public function testFirst()
    {
        $this->assertSame(
            1,
            (new Query([1, 2, 3]))->getFirst()
        );
    }

    /**
     * @depends testFirst
     * @depends testFilter
     */
    public function testFirstFiltered()
    {
        $this->assertSame(
            3,
            (new Query(['A', false, 3]))->filter('is_integer')->getFirst()
        );
    }

    public function testFirstWithNoItems()
    {
        $this->assertNull((new Query)->getFirst());
    }

    public function testIsEmpty()
    {
        $this->assertTrue(
            (new Query([]))->isEmpty()
        );

        $this->assertFalse(
            (new Query([1]))->isEmpty()
        );
    }

    /**
     * @depends testIsEmpty
     * @depends testFilter
     */
    public function testIsEmptyFiltered()
    {
        $this->assertTrue(
            (new Query(['A', null]))->filter('is_integer')->isEmpty()
        );

        $this->assertFalse(
            (new Query([1]))->filter('is_integer')->isEmpty()
        );
    }

    public function testContains()
    {
        $this->assertTrue((new Query(['A']))->contains('A'));
        $this->assertFalse((new Query(['A']))->contains('B'));
    }

    public function testCountable()
    {
        $this->assertSame(
            3,
            count(new Query([1, 2, 3]))
        );
    }

    /**
     * @depends testCountable
     * @depends testFilter
     */
    public function testCountingFilteredValues()
    {
        $this->assertSame(
            3,
            count((new Query([1, $this->prophesizeQueryable([2])->reveal(), 3]))->filter('is_integer'))
        );
    }

    /**
     * @depends testFilter
     */
    public function testAccounts()
    {
        $this->assertSame(
            [$account = $this->prophesizeAccount()->reveal()],
            (new Query([1, $account, 3]))->accounts()->asArray()
        );
    }

    public function testWhereAttribute()
    {
        $attributableProphecy = $this->prophesize(AttributableInterface::CLASS);

        $attributableProphecy->hasAttribute('A')->willReturn(true);
        $attributableProphecy->getAttribute('A')->willReturn('foobar');
        $attributableProphecy->hasAttribute('B')->willReturn(false);

        $attributable = $attributableProphecy->reveal();

        $this->assertSame(
            [$attributable],
            (new Query([1, $attributable, 3]))->whereAttribute('A')->asArray()
        );

        $this->assertSame(
            [],
            (new Query([1, $attributable, 3]))->whereAttribute('B')->asArray()
        );

        $this->assertSame(
            [$attributable],
            (new Query([1, $attributable, 3]))->whereAttribute('A', 'foobar')->asArray()
        );

        $this->assertSame(
            [],
            (new Query([1, $attributable, 3]))->whereAttribute('A', 'not-foobar')->asArray()
        );
    }

    public function testQueryIsQueryable()
    {
        $this->assertSame(
            $query = new Query,
            $query->select()
        );
    }

    /**
     * @depends testFilter
     */
    public function testTransactions()
    {
        $this->assertSame(
            [$transaction = $this->prophesizeTransaction()->reveal()],
            (new Query([1, $transaction, 3]))->transactions()->asArray()
        );
    }

    /**
     * @depends testFilter
     */
    public function testVerifications()
    {
        $this->assertSame(
            [$verification = $this->prophesizeVerification()->reveal()],
            (new Query([1, $verification, 3]))->verifications()->asArray()
        );
    }

    public function testEach()
    {
        $str = '';

        (new Query(['A', 'B', 'C']))->each(function ($letter) use (&$str) {
            $str .= $letter;
        });

        $this->assertSame('ABC', $str);
    }

    public function testLazyOn()
    {
        $sum = 0;

        (new Query([5, 'B', 5]))->lazyOn(
            function ($item) {
                    return is_integer($item);
            },
            function (int $integer) use (&$sum) {
                $sum += $integer;
            }
        )->exec();

        $this->assertSame(10, $sum);
    }

    /**
     * @depends testAsArray
     */
    public function testMap()
    {
        $this->assertSame(
            [10, 20],
            (new Query([0, 10]))->map(function ($integer) {
                return $integer + 10;
            })->asArray()
        );
    }

    /**
     * @depends testMap
     */
    public function testThatMapReturnesNewQuery()
    {
        $query = new Query([0, 10]);

        $this->assertSame(
            [10, 20],
            $query->map(function ($integer) {
                return $integer + 10;
            })->asArray()
        );

        $this->assertSame(
            [0, 10],
            $query->asArray()
        );
    }

    /**
     * @depends testAsArray
     */
    public function testOrderBy()
    {
        $query = new Query([
            $account1000 = $this->prophesizeAccount('1000')->reveal(),
            $account3000 = $this->prophesizeAccount('3000')->reveal(),
            $account2000 = $this->prophesizeAccount('2000')->reveal(),
        ]);

        $this->assertEquals(
            [$account1000, $account2000, $account3000],
            $query->orderBy(function ($left, $right) {
                return $left->getId() <=> $right->getId();
            })->asArray()
        );
    }

    public function testReduce()
    {
        $this->assertSame(
            'ABC',
            (new Query(['A', 'B', 'C']))->reduce(function ($carry, $item) {
                return $carry . $item;
            })
        );
    }

    /**
     * @depends testReduce
     */
    public function testReduceWithInitialValue()
    {
        $this->assertSame(
            'foobar',
            (new Query(['b', 'a', 'r']))->reduce(function ($carry, $item) {
                return $carry . $item;
            }, 'foo')
        );
    }

    /**
     * @depends testAsArray
     */
    public function testWhereUnique()
    {
        $this->assertSame(
            [1, 2, 3],
            (new Query([1, 2, 3, 2]))->whereUnique()->asArray()
        );
    }

    /**
     * @depends testWhereUnique
     */
    public function testUniqueWithObjectItems()
    {
        $queryableA = $this->prophesizeQueryable()->reveal();
        $queryableB = $this->prophesizeQueryable()->reveal();

        $this->assertSame(
            [$queryableA, $queryableB],
            (new Query([$queryableA, $queryableB, $queryableB, $queryableA]))->whereUnique()->asArray()
        );
    }

    /**
     * @depends testAsArray
     */
    public function testWhere()
    {
        $foo = $this->prophesizeQueryable(['', 'foo'])->reveal();
        $bar = $this->prophesizeQueryable(['', 'bar'])->reveal();

        $this->assertSame(
            [$foo],
            (new Query([$foo, $bar]))->filterType(QueryableInterface::CLASS)->where(function ($item) {
                return is_string($item) && $item == 'foo';
            })->asArray(),
            '$bar should be removed as it does not contain the subitem foo'
        );
    }

    /**
     * @depends testAsArray
     */
    public function testWhereNot()
    {
        $foo = $this->prophesizeQueryable(['', 'foo'])->reveal();
        $bar = $this->prophesizeQueryable(['', 'bar'])->reveal();

        $this->assertSame(
            [$bar],
            (new Query([$foo, $bar]))->filterType(QueryableInterface::CLASS)->whereNot(function ($item) {
                return is_string($item) && $item == 'foo';
            })->asArray(),
            '$bar should be kept as it does not contain the subitem foo'
        );
    }

    /**
     * @depends testWhere
     */
    public function testWhereAccount()
    {
        $transA = $this->prophesizeTransaction(null, $this->prophesizeAccount('1234')->reveal())->reveal();
        $transB = $this->prophesizeTransaction(null, $this->prophesizeAccount('1000')->reveal())->reveal();

        $this->assertSame(
            [$transA],
            (new Query([$transA, $transB]))->transactions()->whereAccount('1234')->asArray(),
            'transB should be removed as it does not contain account 1234'
        );
    }

    /**
     * @depends testWhere
     */
    public function testWhereAmountEquals()
    {
        $transA = $this->prophesizeTransaction(new Amount('4'))->reveal();
        $transB = $this->prophesizeTransaction(new Amount('2'))->reveal();

        $verA = $this->prophesizeVerification();
        $verA->getMagnitude()->willReturn(new Amount('3'));
        $verA = $verA->reveal();

        $verB = $this->prophesizeVerification();
        $verB->getMagnitude()->willReturn(new Amount('1'));
        $verB = $verB->reveal();

        $testItems = [$transA, $transB, $verA, $verB];

        $this->assertSame(
            [$transA],
            (new Query($testItems))->whereAmountEquals(new Amount('4'))->asArray()
        );

        $this->assertSame(
            [$verA],
            (new Query($testItems))->whereAmountEquals(new Amount('3'))->asArray()
        );

        return $testItems;
    }

    /**
     * @depends testWhereAmountEquals
     */
    public function testWhereAmountIsGreaterThan(array $testItems)
    {
        $this->assertCount(
            2,
            (new Query($testItems))->whereAmountIsGreaterThan(new Amount('2'))->asArray()
        );

        $this->assertCount(
            1,
            (new Query($testItems))->whereAmountIsGreaterThan(new Amount('3'))->asArray()
        );
    }

    /**
     * @depends testWhereAmountEquals
     */
    public function testWhereAmountIsLessThan(array $testItems)
    {
        $this->assertCount(
            1,
            (new Query($testItems))->whereAmountIsLessThan(new Amount('2'))->asArray()
        );

        $this->assertCount(
            2,
            (new Query($testItems))->whereAmountIsLessThan(new Amount('3'))->asArray()
        );
    }

    public function testGetAccount()
    {
        $this->assertEquals(
            $account = $this->prophesizeAccount('1234', '')->reveal(),
            (new Query(['foo', $account, 'bar']))->getAccount('1234')
        );
    }

    public function testExceptionOnUnknownAccountNumber()
    {
        $this->expectException(Exception\RuntimeException::CLASS);
        $dimension = $this->prophesizeDimension('1234')->reveal();
        (new Query([$dimension]))->getAccount('1234');
    }

    public function testGetDimension()
    {
        $this->assertEquals(
            $dimension = $this->prophesizeDimension('1234')->reveal(),
            (new Query(['foo', $dimension, 'bar']))->getDimension('1234')
        );
    }

    public function testExceptionOnUnknownDimensionNumber()
    {
        $this->expectException(Exception\RuntimeException::CLASS);
        (new Query)->getDimension('1234');
    }

    /**
     * @depends testAsArray
     */
    public function testLimit()
    {
        $query = new Query([1, 2, 3, 4]);

        $this->assertEquals(
            [1, 2],
            $query->limit(2)->asArray()
        );

        $this->assertEquals(
            [1, 2, 3, 4],
            $query->limit(10)->asArray()
        );

        $this->assertEquals(
            [2, 3],
            $query->limit(2, 1)->asArray()
        );

        $this->assertEquals(
            [3, 4],
            $query->limit(100, 2)->asArray()
        );
    }

    /**
     * @depends testAsArray
     */
    public function testLoad()
    {
        $this->assertSame(
            [1, 2, 3, 4],
            (new Query([1, 2]))->load([3, 4])->asArray()
        );
    }

    public function testExceptionOnLoadingUnvalidData()
    {
        $this->expectException(Exception\LogicException::CLASS);
        (new Query)->load(null);
    }

    /**
     * @depends testFilter
     */
    public function testMacro()
    {
        Query::macro('whereInternalType', function ($type) {
            return $this->filter(function ($item) use ($type) {
                return gettype($item) == $type;
            });
        });

        $this->assertSame(
            ['A'],
            (new Query([1, 'A', false]))->whereInternalType('string')->asArray()
        );
    }

    public function testExceptionWhenOverwritingMethodWithMacro()
    {
        $this->expectException(Exception\LogicException::CLASS);
        Query::macro('filter', function () {
        });
    }

    public function testExceptionWhenOverwritingMacro()
    {
        $this->expectException(Exception\LogicException::CLASS);
        Query::macro('thisRareMacroNameIsCreated', function () {
        });
        Query::macro('thisRareMacroNameIsCreated', function () {
        });
    }

    public function testExceptionOnUndefinedMethodCall()
    {
        $this->expectException(Exception\LogicException::CLASS);
        (new Query)->thisMethodDoesNotExist();
    }
}
