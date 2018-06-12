<?php

declare(strict_types = 1);

namespace byrokrat\accounting\Transaction;

use byrokrat\accounting\utils;
use byrokrat\accounting\Dimension\AccountInterface;
use byrokrat\amount\Amount;

class AddedTransactionTest extends \PHPUnit\Framework\TestCase
{
    use utils\PropheciesTrait;

    public function testIsAdded()
    {
        $this->assertTrue(
            (new AddedTransaction(
                $this->createMock(AccountInterface::CLASS),
                $this->createMock(Amount::CLASS),
                ''
            ))->isAdded()
        );
    }

    public function testMandatorySignature()
    {
        $trans = new AddedTransaction(
            $this->createMock(AccountInterface::CLASS),
            $this->createMock(Amount::CLASS),
            'sign'
        );

        $this->assertSame('sign', $trans->getSignature());
    }

    public function testCastToString()
    {
        $this->assertRegExp(
            '/^\(ADDED\)/',
            (string)(new AddedTransaction(
                $this->prophesizeAccount()->reveal(),
                $this->prophesizeAmount()->reveal(),
                ''
            ))
        );
    }
}
