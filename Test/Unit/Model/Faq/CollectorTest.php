<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Unit\Model\Faq;

use MageOS\Faq\Model\Faq\Collector;
use PHPUnit\Framework\TestCase;

class CollectorTest extends TestCase
{
    /**
     * @var Collector
     */
    private Collector $collector;

    protected function setUp(): void
    {
        $this->collector = new Collector();
    }

    public function testStartsEmpty(): void
    {
        $this->assertSame([], $this->collector->getIdentifiers());
    }

    public function testCollectsIdentifiers(): void
    {
        $this->collector->collect('shipping');
        $this->collector->collect('returns');
        $this->assertSame(['shipping', 'returns'], $this->collector->getIdentifiers());
    }

    public function testDeduplicatesIdentifiersPreservingFirstSeenOrder(): void
    {
        $this->collector->collect('shipping');
        $this->collector->collect('returns');
        $this->collector->collect('shipping');
        $this->assertSame(['shipping', 'returns'], $this->collector->getIdentifiers());
    }

    public function testNumericIdentifiersComeBackAsStrings(): void
    {
        // Issue #2: kept as array keys, "123" came back as the integer 123, and the JSON-LD block's
        // strictly typed source lookup threw a TypeError while rendering the page.
        foreach (['0', '123', '-7', '01'] as $identifier) {
            $this->collector->collect($identifier);
        }

        $this->assertSame(['0', '123', '-7', '01'], $this->collector->getIdentifiers());
    }

    public function testIgnoresEmptyIdentifier(): void
    {
        $this->collector->collect('');
        $this->assertSame([], $this->collector->getIdentifiers());
    }

    public function testResetStateClearsCollectedIdentifiers(): void
    {
        $this->collector->collect('shipping');
        $this->collector->collect('returns');

        $this->collector->_resetState();

        $this->assertSame([], $this->collector->getIdentifiers());
    }

    public function testCollectsFreshIdentifiersAfterReset(): void
    {
        $this->collector->collect('shipping');
        $this->collector->_resetState();

        $this->collector->collect('warranty');

        $this->assertSame(['warranty'], $this->collector->getIdentifiers());
    }
}
