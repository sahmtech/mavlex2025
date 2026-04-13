<?php

namespace Tests\Unit\Accounting;

use Modules\Accounting\Utils\JournalEntryValidator;
use PHPUnit\Framework\TestCase;

class JournalEntryValidatorTest extends TestCase
{
    public function test_balanced_journal_passes(): void
    {
        $parse = fn ($v) => (float) $v;
        $accounts = ['1', '2'];
        $debits = ['100', '0'];
        $credits = ['0', '100'];
        $r = JournalEntryValidator::validateJournalLines($accounts, $debits, $credits, $parse);
        $this->assertTrue($r['ok']);
        $this->assertEquals(100.0, $r['total_debit']);
        $this->assertEquals(100.0, $r['total_credit']);
    }

    public function test_unbalanced_fails(): void
    {
        $parse = fn ($v) => (float) $v;
        $accounts = ['1', '2'];
        $debits = ['100', '50'];
        $credits = ['0', '0'];
        $r = JournalEntryValidator::validateJournalLines($accounts, $debits, $credits, $parse);
        $this->assertFalse($r['ok']);
        $this->assertSame('unbalanced', $r['error']);
    }

    public function test_both_sides_fails(): void
    {
        $parse = fn ($v) => (float) $v;
        $accounts = ['1'];
        $debits = ['10'];
        $credits = ['10'];
        $r = JournalEntryValidator::validateJournalLines($accounts, $debits, $credits, $parse);
        $this->assertFalse($r['ok']);
        $this->assertSame('both_sides', $r['error']);
    }
}
